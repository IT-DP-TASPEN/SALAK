<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchOffice extends BaseModel
{
    protected $guarded = [];

    public function proyeksiLendings(): HasMany
    {
        return $this->hasMany(ProyeksiLending::class, 'lending_kantor', 'id');
    }

    public function proyeksiFundings(): HasMany
    {
        return $this->hasMany(ProyeksiFunding::class, 'funding_kantor', 'id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_office_id', 'id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'agent_branch_office', 'id');
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class, 'cash_kantor', 'id');
    }

    public function loanOutstandings(): HasMany
    {
        return $this->hasMany(LoanOutstanding::class, 'loan_branch_office', 'branch_code_fincloud');
    }

    public static function konsolidasiMIAPB(?string $tanggal = null, ?string $branch = null): float
    {
        $modalInti = static::konsolidasiModalInti($tanggal, $branch);
        $ppap = static::konsolidasiPPKAKhusus($tanggal, $branch);

        return $ppap > 0 ? ($modalInti / $ppap) * 100 : 0.0;
    }

    public static function konsolidasiPAR(?string $tanggal = null, ?string $branch = null): float
    {
        $par = LoanOutstanding::query()
            ->selectRaw(
                'SUM(CASE WHEN loan_days_past_due > 1 AND loan_days_past_due <= 90 THEN loan_outstanding END) * 1.0
                / NULLIF(SUM(loan_outstanding), 0) * 1.0 * 100 as par_percentage'
            )
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->where('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->value('par_percentage');

        return $par ?? 0.0;
    }

    public function npl(?string $tanggal = null): float
    {
        return static::konsolidasiNPL($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiNPL(?string $tanggal = null, ?string $branch = null): float
    {
        $npl = LoanOutstanding::query()
            ->selectRaw(
                'SUM(CASE WHEN loan_bi_collectability IN (3, 4, 5) THEN loan_outstanding END) * 1.0
                / NULLIF(SUM(loan_outstanding), 0) * 1.0 * 100 as npl_percentage'
            )
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->where('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->value('npl_percentage');

        return $npl ?? 0.0;
    }

    public function nplNett(?string $tanggal = null): float
    {
        return static::konsolidasiNPLNett($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiNPLNett(?string $tanggal = null, ?string $branch = null): float
    {
        // $ckpn = array_sum(BranchOffice::saldoNeraca2(['1272005'], $branch, $tanggal));
        $ppka = static::konsolidasiPPKAKhusus($tanggal, $branch);

        $npl = LoanOutstanding::query()
            ->selectRaw(
                '(SUM(CASE WHEN loan_bi_collectability IN (3, 4, 5) THEN loan_outstanding END) - ?) * 1.0
                / NULLIF(SUM(loan_outstanding), 0) * 1.0 * 100 as npl_percentage',
                [$ppka]
            )
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->where('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->value('npl_percentage');

        return $npl ?? 0.0;
    }

    public function modalInti(?string $tanggal = null): float
    {
        return static::konsolidasiModalInti($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiModalInti(?string $tanggal = null, ?string $branch = null): float
    {
        $total = array_sum(
            static::saldoNeraca2(
                [
                    '300', // Authorized Capital
                    '3111000', // General Reserve
                    '3111001', // Goal Reserve
                    '321', // Past Years Retained Earning
                    '322', // Last Year Retained Earning
                    '323', // This Year Retained Earning
                ],
                $branch,
                $tanggal
            )
        );

        $total -= abs(
            array_sum(
                static::saldoNeraca2(
                    [
                        '302', // Unpaid Capital
                    ],
                    $branch,
                    $tanggal
                )
            )
        );

        $ckpn = array_sum(
            static::saldoNeraca2(
                [
                    '1272005', // Provisioning - CKPN
                ],
                $branch,
                $tanggal
            )
        );
        $ppka = static::konsolidasiPPKA($tanggal, $branch);

        if ($ckpn < $ppka) {
            $total -= $ppka - $ckpn;
        }

        return $total;
    }

    public function modalPelengkap(?string $tanggal = null): float
    {
        return static::konsolidasiModalPelengkap($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiModalPelengkap(?string $tanggal = null, ?string $branch = null): float
    {
        $weights = [
            // PPKA Umum
            1 => 0.005, // 0.5%
            2 => 0.03,  // 3%
        ];

        $totals = LoanOutstanding::query()
            ->select('loan_bi_collectability', DB::raw('SUM(loan_outstanding) as total_outstanding'))
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->where('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->whereIn('loan_bi_collectability', array_keys($weights))
            ->groupBy('loan_bi_collectability')
            ->pluck('total_outstanding', 'loan_bi_collectability');

        $ppkaUmum = 0.0;
        foreach ($totals as $collectability => $totalOutstanding) {
            $ppkaUmum += (float) $totalOutstanding * $weights[$collectability];
        }

        $threshold = 2_000_000_000;
        $sumPerPrefix = function (string $prefix) use ($tanggal, $branch, $threshold) {
            return SaldoNeraca::query()
                ->where('tanggal', $tanggal)
                ->when($branch, fn($q) => $q->where('cabang', $branch))
                ->where('noakun', 'like', $prefix . '%')
                ->whereRaw('LENGTH(noakun) = 7')
                ->where('saldoakhir', '>', 0)
                ->selectRaw(
                    'SUM(saldoakhir - CASE WHEN saldoakhir >= ? THEN ? ELSE 0 END) AS total',
                    [$threshold, $threshold]
                )
                ->value('total') ?? 0;
        };

        $abaTotal =
            $sumPerPrefix('111') + // Giro
            $sumPerPrefix('112') + // Tabungan
            $sumPerPrefix('113'); // Deposito

        return $ppkaUmum + max(0.0, $abaTotal * 0.005);
    }

    public static function konsolidasiATMR(?string $tanggal = null, ?string $branch = null): float
    {
        $GLWeights = [
            '100' => 0.0,    // Cash (0%)
            '110' => 0.2,    // Placement In Other Banks (20%)
            '150' => 1.0,
            '197' => 1.0,
            '196' => 1.0,
            '195' => 1.0,
            '194' => 1.0,
            '193' => 1.0,
            '192' => 1.0,
            '191' => 1.0,
            '184' => 1.0,
            '183' => 1.0,
            '182' => 1.0,
            '181' => 1.0,
            '171' => 1.0,
            '163' => 1.0,
            '164' => 1.0,
            '162' => 1.0,
            '161' => 1.0,
            '132' => 1.0,
            '131' => 1.0,
            '129' => 1.0,
        ];

        $tanggal ??= Carbon::today()->toDateString();
        $totalATMR = 0.0;

        // ========== 1) ATMR dari GL ==========
        $keys = array_keys($GLWeights);
        $neraca = static::saldoNeraca2($keys, $branch, $tanggal);

        foreach ($neraca as $perkKode => $saldo) {
            $totalATMR += (float) $saldo * (float) ($GLWeights[$perkKode] ?? 0.0);
        }

        // Base query loan (biar konsisten filter cabang & tanggal)
        $loanBase = LoanOutstanding::query()
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->where('loan_date_params', $tanggal);

        $excluded = collect(); // kumpulin loan_account yang udah “diklasifikasi”
        // $excludedArr = fn() => $excluded->unique()->values()->all();

        // ========== 2) Bad loans (100%) ==========
        $badLoans = (clone $loanBase)
            ->where('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->where('loan_bi_collectability', 5)
            ->select(['loan_account', 'loan_outstanding'])
            ->get();

        $totalATMR += (float) $badLoans->sum('loan_outstanding') * 1.0;
        $excluded = $excluded->merge($badLoans->pluck('loan_account'));

        // ========== 3) Loans dengan agunan land/building (30%) ==========
        // Catatan: gue ambil daftar account dari collateral, tapi exposure-nya tetep dari LoanOutstanding (snapshot tanggal yg sama),
        // biar gak ke-dobel gara-gara collateral multi-row / outstanding collateral beda definisi.
        $landAccounts = LoanCollateralList::query()
            ->where('fetch_date', $tanggal ?? Carbon::today()->toDateString())
            ->whereNotIn('loan_acc_no', $excluded->unique()->values()->all())
            ->where(function ($q) {
                $q->where('collateral_type', 'like', '%land%')
                    ->orWhere('collateral_type', 'like', '%building%');
            })
            ->distinct()
            ->pluck('loan_acc_no');

        if ($landAccounts->isNotEmpty()) {
            $landOutstanding = (clone $loanBase)
                ->whereIn('loan_account', $landAccounts->all())
                ->sum('loan_outstanding');

            $totalATMR += (float) $landOutstanding * 0.30;
            $excluded = $excluded->merge($landAccounts);
        }

        // ========== 4) Golongan 874 (50%) ==========
        $candidates874 = (clone $loanBase)
            ->whereNotIn('loan_account', $excluded->unique()->values()->all())
            ->where(function ($q) use ($tanggal) {
                $q->whereHas('msoLoanAtmr', function ($q) {
                    $q->where('loan_golongan_debitur', '874')
                        ->whereNotIn('loan_jenis_usaha', ['1', '2']); // bukan UMK
                })
                    ->orWhere(function ($q) use ($tanggal) {
                        $q->whereDoesntHave('msoLoanAtmr')
                            ->whereHas('cbrCustomer', function ($q) use ($tanggal) {
                                $q
                                    ->where('fetch_date', $tanggal ?? Carbon::today()->toDateString())
                                    ->where('owner_group', '874')
                                    ->whereNotIn('debtor_group', ['UK', 'UM']); // bukan UMK
                            });
                    });
            })
            ->select(['loan_account', 'loan_cif', 'loan_outstanding', 'loan_principal', 'loan_installment_loans'])
            ->with(['latestDapem:payroll_dapem_masters.customer_id,nominal_dapem,bulan_dapem'])
            ->get();

        $loans874 = $candidates874->filter(function ($loan) {
            if (($loan->loan_principal ?? 0) <= 200_000_000) {
                return true;
            }

            $dapem = $loan->latestDapem;
            $nominal = (float) ($dapem->nominal_dapem ?? 0);

            if ($nominal <= 0) {
                return false;
            }

            $ratio = ((float) ($loan->loan_installment_loans ?? 0) / $nominal) * 100.0;
            return $ratio <= 30.0;
        });

        $totalATMR += (float) $loans874->sum('loan_outstanding') * 0.50;
        $excluded = $excluded->merge($loans874->pluck('loan_account'));

        $umkLoans = (clone $loanBase)
            ->whereNotIn('loan_account', $excluded->unique()->values()->all())
            ->where(function ($q) use ($tanggal) {
                $q->doesntHave('cbrCustomer')
                    ->whereHas('msoLoanAtmr', function ($q) {
                        $q->whereIn('loan_jenis_usaha', ['1', '2']) // Mikro & Kecil
                            ->whereNotIn('loan_golongan_debitur', ['874', '875']);
                    })->orWhere(function ($q) use ($tanggal) {
                        $q->doesntHave('msoLoanAtmr')
                            ->whereHas('cbrCustomer', function ($q) use ($tanggal) {
                                $q
                                    ->where('fetch_date', $tanggal ?? Carbon::today()->toDateString())
                                    ->whereIn('debtor_group', ['UK', 'UM'])
                                    ->whereNotIn('owner_group', ['874', '875']);
                            });
                    });
            })
            ->select(['loan_account', 'loan_outstanding'])
            ->get();

        $totalATMR += (float) $umkLoans->sum('loan_outstanding') * 0.70;
        $excluded = $excluded->merge($umkLoans->pluck('loan_account'));

        // ========== 6) Remaining loans (100%) ==========
        $remainingOutstanding = (clone $loanBase) // 875
            ->whereNotIn('loan_account', $excluded->unique()->values()->all())
            ->sum('loan_outstanding');

        $totalATMR += (float) $remainingOutstanding * 1.0;

        return $totalATMR;
    }

    public static function konsolidasiKPMM(?string $tanggal = null, ?string $branch = null): float
    {
        $modalInti = static::konsolidasiModalInti($tanggal, $branch);
        $modalPelengkap = static::konsolidasiModalPelengkap($tanggal, $branch);

        $modal = $modalInti + $modalPelengkap;
        if ($modal == 0.0) {
            return 0.0;
        }

        $atmr = static::konsolidasiATMR($tanggal, $branch);
        if ($atmr == 0.0) {
            return 0.0;
        }

        $kpmm = $modal / $atmr * 100;

        // print_r([
        //     'modal_inti' => number_format($modalInti, 2, ',', '.'),
        //     'modal_pelengkap' => number_format($modalPelengkap, 2, ',', '.'),
        //     'atmr' => number_format($atmr, 2, ',', '.'),
        //     'kpmm' => number_format($kpmm, 2, ',', '.') . '%',
        // ]);

        return $kpmm;
    }

    public function ppka(?string $tanggal = null): float
    {
        return static::konsolidasiPPKA($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiPPKA(?string $tanggal = null, ?string $branch = null): float
    {
        $weights = [
            // PPKA Umum
            1 => 0.005, // 0.5%
            2 => 0.03,  // 3%
            // PPKA Khusus
            3 => 0.10,  // 10%
            4 => 0.50,  // 50%
            5 => 1.00,  // 100%
        ];

        $totals = LoanOutstanding::query()
            ->select('loan_bi_collectability', DB::raw('SUM(loan_outstanding) as total_outstanding'))
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->where('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->whereIn('loan_bi_collectability', array_keys($weights))
            ->groupBy('loan_bi_collectability')
            ->pluck('total_outstanding', 'loan_bi_collectability');

        $ppka = 0.0;
        foreach ($totals as $collectability => $totalOutstanding) {
            $ppka += (float) $totalOutstanding * $weights[$collectability];
        }

        return $ppka;
    }

    public static function konsolidasiPPKAKhusus(?string $tanggal = null, ?string $branch = null): float
    {
        $weights = [
            // PPKA Khusus
            3 => 0.10,  // 10%
            4 => 0.50,  // 50%
            5 => 1.00,  // 100%
        ];

        $totals = LoanOutstanding::query()
            ->select('loan_bi_collectability', DB::raw('SUM(loan_outstanding) as total_outstanding'))
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->where('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->whereIn('loan_bi_collectability', array_keys($weights))
            ->groupBy('loan_bi_collectability')
            ->pluck('total_outstanding', 'loan_bi_collectability');

        $ppka = 0.0;
        foreach ($totals as $collectability => $totalOutstanding) {
            $ppka += (float) $totalOutstanding * $weights[$collectability];
        }

        return $ppka;
    }

    public function pendapatanOperasional(?string $tanggal = null): float
    {
        return static::konsolidasiPendapatanOperasional($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiPendapatanOperasional(?string $tanggal = null, ?string $branch = null): float
    {
        $pendapatan = array_sum(
            static::saldoNeraca2(
                [
                    '4', // Income
                ],
                $branch,
                $tanggal
            )
        );

        return $pendapatan;
    }

    public function bebanOperasional(?string $tanggal = null): float
    {
        return static::konsolidasiBebanOperasional($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiBebanOperasional(?string $tanggal = null, ?string $branch = null): float
    {
        $beban = static::saldoNeraca2(
            [
                '5', // Expenses
                '558', // Income Tax Expense
            ],
            $branch,
            $tanggal
        );

        return $beban['5'] - $beban['558'];
    }

    public function CKPNPerPPKA(?string $tanggal = null): float
    {
        return static::konsolidasiCKPNPerPPKA($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiCKPNPerPPKA(?string $tanggal = null, ?string $branch = null): float
    {
        $ckpn = array_sum(
            static::saldoNeraca2(
                [
                    '1272005', // Provisioning - CKPN
                ],
                $branch,
                $tanggal
            )
        );

        $ppka = static::konsolidasiPPKA($tanggal, $branch);
        if ($ppka == 0.0) {
            return 0.0;
        }

        return $ckpn / $ppka * 100;
    }

    public function bopo(?string $tanggal = null): float
    {
        return static::konsolidasiBopo($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiBopo(?string $tanggal = null, ?string $branch = null): float
    {
        $bebanOperasional = static::konsolidasiBebanOperasional($tanggal, $branch);
        $pendapatanOperasional = static::konsolidasiPendapatanOperasional($tanggal, $branch);

        if ($pendapatanOperasional == 0.0) {
            return 0.0;
        }

        $month = $tanggal ? Carbon::parse($tanggal)->month : Carbon::today()->month;
        $monthsInYear = 12;
        $bebanOperasional = $bebanOperasional / $month * $monthsInYear;
        $pendapatanOperasional = $pendapatanOperasional / $month * $monthsInYear;

        return $bebanOperasional / $pendapatanOperasional * 100;
    }

    public function nim(?string $tanggal = null): float
    {
        return static::konsolidasiNim($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiNim(?string $tanggal = null, ?string $branch = null): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $month = $asOf->month;

        $pendapatanBunga = 0.0;
        $bebanBunga = 0.0;
        $asetProduktif = 0.0;

        if ($asOf->isBefore('2025-10-12')) {
            $pendapatanBunga = array_sum(
                static::saldoNeracaMso(
                    [
                        '2.112',   // Pend. ABA Giro
                        '2.113',   // Pend. ABA Tabungan
                        '2.115',   // Pend. ABA Deposito
                        '2.120.1', // Pend. Bg Kredit : Baki Debet
                    ],
                    $branch,
                    $asOf->toDateString(),
                )
            );

            $bebanBunga = array_sum(
                static::saldoNeracaMso(
                    [
                        '2.171', // DPK Non Bank Tabungan
                        '2.172', // DPK Non Bank Deposito Berjangka
                        '2.166', // ABA Tabungan
                        '2.167', // ABA Deposito
                        '2.168', // ABA Pinjaman yang Diterima
                    ],
                    $branch,
                    $asOf->toDateString()
                )
            );
        } else {
            $pendapatanBunga = array_sum(
                static::saldoNeraca2(
                    [
                        '401', // Current Account Interest Income
                        '402', // Savings Account Interest Income
                        '403', // Time Deposit Interest Income
                        '410', // Loan Interest Income
                    ],
                    $branch,
                    $asOf->toDateString()
                )
            );

            $bebanBunga = array_sum(
                static::saldoNeraca2(
                    [
                        '501', // Savings Interest Expenses
                        '502', // Time Deposits Interest Expenses
                        '511', // Expenses - Loan Interest Fees
                    ],
                    $branch,
                    $asOf->toDateString()
                )
            );
        }


        for ($m = 1; $m <= $month; $m++) {
            $tanggal = $asOf->copy()->setMonth($m);

            if ($tanggal->isBefore('2025-10-12')) {
                $asetProduktif += array_sum(
                    static::saldoNeracaMso(
                        [
                            '1.120', // Antar Bank Aktiva
                            '1.130.1', // Kredit Yang Diberikan
                        ],
                        $branch,
                        $tanggal->toDateString()
                    )
                );
            } else {
                $asetProduktif += array_sum(
                    static::saldoNeraca2(
                        [
                            '110', // Placement In Other Banks
                            '121', // Loans
                        ],
                        $branch,
                        $tanggal->toDateString()
                    )
                );
            }
        }

        $monthsInYear = 12;
        $pendapatanBunga = $pendapatanBunga / $month * $monthsInYear;
        $bebanBunga = $bebanBunga / $month * $monthsInYear;
        $pendapatanBunga -= $bebanBunga;
        $asetProduktif = $asetProduktif / $month;

        if ($asetProduktif == 0.0) {
            return 0.0;
        }

        return $pendapatanBunga / $asetProduktif * 100;
    }

    public function kewajibanLancar(?string $tanggal = null): float
    {
        return static::konsolidasiKewajibanLancar($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiKewajibanLancar(?string $tanggal = null, ?string $branch = null): float
    {
        /**
         * 1.200 : Kewajiban yang Segera Dapat Dibayar
         *   - 1.200.10 : Kewajiban kepada Pemerintah yang harus dibayar 
         *     - 211 : Tax Payable - Saving Account Interest
         *     - 212 : Tax Payable - Interest Time Deposit Account
         *     - 213 : Tax Payable - Employees
         *     - 219 : Tax Payable - Honorary
         *   - 1.200.20 : Kewajiban yang telah JT
         *     - 2011008 : Deposit - Interest Due
         *   - 1.200.30 : Titipan Nasabah
         *     - 2011001 : Deposit - Third Parties
         *     - 2011004 : Debtor Customer Deposit / Temporary
         *     - 2011005 : Taspen Pension Customer Deposits
         *     - 2011006 : Deposits for Retired Civil Servants
         *     - 2011007 : DP Taspen Pension Deposit
         *     - 208 : Insurance
         * 1.210 : Tabungan
         *   - 221 : Savings
         * 1.220 : Deposito Berjangka
         *   - 2312200 : Time Deposit
         *   - 2312201 : Time Deposit Compound
         */

        $total = array_sum(
            static::saldoNeraca2(
                [
                    '211',
                    '212',
                    '213',
                    '219',
                    '2011008',
                    '2011001',
                    '2011004',
                    '2011005',
                    '2011006',
                    '2011007',
                    '208',
                    '221',
                    '2312200',
                    '2312201',
                ],
                $branch,
                $tanggal
            )
        );

        return $total;
    }

    public function roa(?string $tanggal = null): float
    {
        return static::konsolidasiROA($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiROA(?string $tanggal = null, ?string $branch = null): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $month = $asOf->month;

        $labaBeforeTax = 0.0;
        if ($asOf->isBefore('2025-10-12')) {
            $labaBeforeTax = array_sum(
                static::saldoNeracaMso(
                    [
                        '2.330', // Laba / Rugi Tahun Berjalan (Sebelum Pajak)
                    ],
                    $branch,
                    $asOf->toDateString()
                )
            );
        } else {
            $labaBeforeTax = array_sum(
                static::saldoNeraca2(
                    [
                        '323', // Current Year Retained Earning
                        '558', // Income Tax
                    ],
                    $branch,
                    $asOf->toDateString()
                )
            );
        }

        $totalAsset = 0.0;

        for ($m = 1; $m <= $month; $m++) {
            $tgl = $asOf->copy()->setMonth($m);
            if ($tgl->isBefore('2025-10-12')) {
                $aktiva = array_sum(
                    static::saldoNeracaMso(
                        [
                            '1.190', // Jumlah Aktiva
                        ],
                        $branch,
                        $tgl->toDateString()
                    )
                );
                $aka = array_sum(
                    static::saldoNeracaMso(
                        [
                            '1.170', // Antar Kantor Aktiva
                        ],
                        $branch,
                        $tgl->toDateString()
                    )
                );
                $totalAsset += $aktiva - $aka;
            } else {
                $totalAsset += array_sum(
                    static::saldoNeraca2(
                        [
                            '1', // Assets
                        ],
                        $branch,
                        $tgl->toDateString()
                    )
                );
            }
        }

        $monthsInYear = 12;
        $pendapatanBersih = $labaBeforeTax / $month * $monthsInYear;
        $rataRataTotalAset = $totalAsset / $month;

        if ($rataRataTotalAset == 0.0) {
            return 0.0;
        }

        return $pendapatanBersih / $rataRataTotalAset * 100;
    }

    public function cashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        return static::konsolidasiCashRatio($tanggal, $simulated, $efektif, $this->branch_code_fincloud);
    }

    public static function konsolidasiCashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true, ?string $branch = null): float
    {
        $totalLiquid = static::konsolidasiAssetLiquid($tanggal, $simulated, $efektif, $branch);
        $totalKewajibanLancar = static::konsolidasiKewajibanLancar($tanggal, $branch);

        return $totalKewajibanLancar == 0.0 ? 0.0 : ($totalLiquid / $totalKewajibanLancar * 100);
    }

    public function loanToDepositRatio(?string $tanggal = null, bool $simulated = false): float
    {
        return static::konsolidasiLDR($tanggal, $simulated, $this->branch_code_fincloud);
    }

    public static function konsolidasiLDR(?string $tanggal = null, bool $simulated = false, ?string $branch = null): float
    {
        $components = static::konsolidasiLDRComponents($tanggal, $simulated, $branch);
        $totalBakiDebet = $components['total_baki_debet'];
        $totalSimpanan = $components['total_simpanan'];

        return $totalSimpanan == 0.0 ? 0.0 : ($totalBakiDebet / $totalSimpanan * 100);
    }

    /**
     * @return array{total_baki_debet: float, total_simpanan: float}
     */
    public static function konsolidasiLDRComponents(?string $tanggal = null, bool $simulated = false, ?string $branch = null): array
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $tanggal = $asOf->toDateString();
        $totalBakiDebet = array_sum(static::saldoNeraca2(['121', '122'], $branch, $tanggal));

        if ($simulated) {
            $proyeksiLendings = ProyeksiLending::query()
                ->when($branch, fn($q) => $q->whereHas('branchOffice', fn($q2) => $q2->where('branch_code_fincloud', $branch)))
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->where('lending_tanggal', $tanggal)
                ->sum('lending_booking_bersih');
            $totalBakiDebet += $proyeksiLendings;
        }

        $simpanan = array_sum(static::saldoNeraca2(['221', '2312200', '2312201'], $branch, $tanggal));
        // exclude savings internal and savings abp
        $totalSimpanan = $simpanan - (array_sum(static::saldoNeraca2(['2212111', '2212116', '2212199'], $branch, $tanggal)));

        return [
            'total_baki_debet' => (float) $totalBakiDebet,
            'total_simpanan' => (float) $totalSimpanan,
        ];
    }

    public static function konsolidasiCashRatio2(?string $tanggal = null, float $totalLiquid = 0.0): float
    {
        $totalKewajibanLancar = static::konsolidasiKewajibanLancar($tanggal);
        return $totalKewajibanLancar == 0.0 ? 0.0 : ($totalLiquid / $totalKewajibanLancar * 100);
    }

    public static function konsolidasiSaldoKas(?string $tanggal = null, ?string $branch = null): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $asOf = $asOf->toDateString();

        $neraca = static::saldoNeraca2(['100'], $branch, $asOf);
        return array_sum($neraca);
    }

    public static function konsolidasiAssetLiquid(?string $tanggal = null, bool $simulated = false, bool $efektif = true, ?string $branch = null): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $asOf = $asOf->toDateString();

        $giroTab = array_sum(static::saldoNeraca2(['111', '112'], $branch, $asOf));
        $kas = static::konsolidasiSaldoKas($asOf, $branch);
        $ret = $kas + $giroTab;
        if ($efektif) {
            if ($branch) {
                $ret -= BranchOffice::where('branch_code_fincloud', $branch)->sum('branch_saldo_aba_blokir');
            } else {
                $ret -= BranchOffice::sum('branch_saldo_aba_blokir');
            }
        }

        if ($simulated) {
            $proyeksiCashIns = CashFlow::query()
                ->when($branch, fn($q) => $q->whereHas('branchOffice', fn($q2) => $q2->where('branch_code_fincloud', $branch)))
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash In'))
                ->where('cash_tanggal', $asOf)
                ->sum('cash_jumlah');
            $ret += $proyeksiCashIns;

            $proyeksiCashOuts = CashFlow::query()
                ->when($branch, fn($q) => $q->whereHas('branchOffice', fn($q2) => $q2->where('branch_code_fincloud', $branch)))
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash Out'))
                ->where('cash_tanggal', $asOf)
                ->sum('cash_jumlah');
            $ret -= $proyeksiCashOuts;
        }

        return $ret;
    }

    public static function saldoNeraca2(array $kodePerkiraanList, ?string $branchCode = null, ?string $tanggal = null): array
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        if ($asOf->isFuture()) {
            $asOf = Carbon::today();
        }

        $rows = SaldoNeraca::query()
            ->selectRaw('noakun as perk_kode')
            ->selectRaw("
            SUM(
                COALESCE(saldoakhir, 0) *
                CASE
                    WHEN noakun IN ('1272005') THEN -1
                    WHEN SUBSTR(noakun, 1, 1) IN ('2','3','4','6') THEN -1
                    ELSE 1
                END
            ) as saldo
        ")
            ->when($branchCode, fn($q) => $q->where('cabang', $branchCode))
            ->whereIn('noakun', $kodePerkiraanList)
            ->where('tanggal', $asOf->toDateString())
            ->groupBy('noakun')              // lebih aman daripada groupBy alias
            ->pluck('saldo', 'perk_kode');

        return $rows->toArray();
    }

    public static function saldoNeracaMso(array $kodePerkiraanList, ?string $branchCode = null, ?string $tanggal = null): array
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        if ($asOf->isFuture()) {
            $asOf = Carbon::today();
        }

        if ($branchCode !== null && strlen($branchCode) === 3) {
            // normalize branch code to 2 digits (001 -> 01, 002 -> 02, etc)
            $branchCode = $branchCode[1] . $branchCode[2];
        }

        $rows = Collection::make();
        foreach ($kodePerkiraanList as $perk) {
            $row = DB::connection('mso-backup')
                ->query()
                ->selectRaw(
                    'GetPerkSaldo(?, ?, ?) as saldo',
                    [
                        $perk,
                        $branchCode,
                        $asOf->toDateString(),
                    ]
                )
                ->first();
            $rows->put($perk, $row->saldo ?? 0.0);
        }

        return $rows->toArray();
    }

    public function fincloudSaldoNeraca(): HasMany
    {
        return $this->hasMany(SaldoNeraca::class, 'cabang', 'branch_code_fincloud');
    }
}
