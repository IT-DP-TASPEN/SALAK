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
            ->whereDate('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->value('npl_percentage');

        return $npl ?? 0.0;
    }

    public function nplNett(?string $tanggal = null): float
    {
        return static::konsolidasiNPLNett($tanggal, $this->branch_code_fincloud);
    }

    public static function konsolidasiNPLNett(?string $tanggal = null, ?string $branch = null): float
    {
        $ckpn = array_sum(BranchOffice::saldoNeraca2(['1272005'], $branch, $tanggal));

        $npl = LoanOutstanding::query()
            ->selectRaw(
                '(SUM(CASE WHEN loan_bi_collectability IN (3, 4, 5) THEN loan_outstanding END) - ?) * 1.0
                / NULLIF(SUM(loan_outstanding), 0) * 1.0 * 100 as npl_percentage',
                [$ckpn]
            )
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->whereDate('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
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
                    '322', // Last Year Retained Earning
                    '323', // This Year Retained Earning
                ],
                $branch,
                $tanggal
            )
        );

        $total -= array_sum(
            static::saldoNeraca2(
                [
                    '302', // Unpaid Capital
                ],
                $branch,
                $tanggal
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
            ->whereDate('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
            ->whereIn('loan_bi_collectability', array_keys($weights))
            ->groupBy('loan_bi_collectability')
            ->pluck('total_outstanding', 'loan_bi_collectability');

        $ppkaUmum = 0.0;
        foreach ($totals as $collectability => $totalOutstanding) {
            $ppkaUmum += (float) $totalOutstanding * $weights[$collectability];
        }

        $abaTotal = array_sum(
            static::saldoNeraca2(
                [
                    '110', // Placement In Other Banks
                ],
                $branch,
                $tanggal
            )
        );

        // TODO: data ini nanti harus diambil dari db
        $abaTotal -= 2_000_000_000; // dikurangi 2 miliar

        return $ppkaUmum + max(0.0, $abaTotal);
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
            ->whereDate('loan_date_params', $tanggal ?? Carbon::today()->toDateString())
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
        $beban = array_sum(
            static::saldoNeraca2(
                [
                    '5', // Expenses
                ],
                $branch,
                $tanggal
            )
        );

        return $beban;
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
                        '312', // Estimated Income Tax
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
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $tanggal = $asOf->toDateString();
        $totalBakiDebet = array_sum(static::saldoNeraca2(['121'], $branch, $tanggal));
        if ($simulated) {
            $proyeksiLendings = ProyeksiLending::query()
                ->when($branch, fn($q) => $q->whereHas('branchOffice', fn($q2) => $q2->where('branch_code_fincloud', $branch)))
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereDate('lending_tanggal', $tanggal)
                ->sum('lending_booking_bersih');
            $totalBakiDebet += $proyeksiLendings;
        }
        $totalSimpanan = array_sum(static::saldoNeraca2(['221', '2312200', '2312201'], $branch, $tanggal));

        return $totalSimpanan == 0.0 ? 0.0 : ($totalBakiDebet / $totalSimpanan * 100);
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

        $neraca = static::saldoNeraca2(['1011000'], $branch, $asOf);
        return array_sum($neraca);
    }

    public static function konsolidasiAssetLiquid(?string $tanggal = null, bool $simulated = false, bool $efektif = true, ?string $branch = null): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $asOf = $asOf->toDateString();

        $giroTab = array_sum(static::saldoNeraca2(['111', '112'], $branch, $asOf));
        $kas = static::konsolidasiSaldoKas($asOf, $branch);

        $ret = $kas + $giroTab;
        if ($efektif && ($branch === null || $branch === '001')) {
            $ret -= BranchOffice::sum('branch_saldo_aba_blokir');
        }

        if ($simulated) {
            $proyeksiCashIns = CashFlow::query()
                ->when($branch, fn($q) => $q->whereHas('branchOffice', fn($q2) => $q2->where('branch_code_fincloud', $branch)))
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash In'))
                ->whereDate('cash_tanggal', $asOf)
                ->sum('cash_jumlah');
            $ret += $proyeksiCashIns;

            $proyeksiCashOuts = CashFlow::query()
                ->when($branch, fn($q) => $q->whereHas('branchOffice', fn($q2) => $q2->where('branch_code_fincloud', $branch)))
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash Out'))
                ->whereDate('cash_tanggal', $asOf)
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
            ->select(
                'noakun as perk_kode',
                DB::raw('SUM(COALESCE(saldoakhir, 0)) as saldo'),
            )
            ->when($branchCode, fn($q) => $q->where('cabang', $branchCode))
            ->whereIn('noakun', $kodePerkiraanList)
            ->whereDate('tanggal', $asOf->toDateString())
            ->groupBy('perk_kode')
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
