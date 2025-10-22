<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function saldoKas(?string $tanggal = null): float
    {
        return $this->fincloudSaldoKas($tanggal);
    }

    public function assetLiquid(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        return $this->fincloudAssetLiquid($tanggal, $simulated, $efektif);
    }

    public function loanOutstandings(): HasMany
    {
        return $this->hasMany(LoanOutstanding::class, 'loan_branch_office', 'branch_code_fincloud');
    }

    public function npl(): float
    {
        $loans = $this->loanOutstandings()->get();
        $totalBakiDebet = $loans->sum('loan_outstanding');
        if ($totalBakiDebet == 0.0) {
            return 0.0;
        }

        $nplLoans = $loans->filter(fn($loan) => !in_array($loan->loan_bi_collectability, [1, 2]));
        $totalNpl = $nplLoans->sum('loan_outstanding');

        return $totalNpl / $totalBakiDebet * 100;
    }

    public static function konsolidasiNPL(): float
    {
        $loans = LoanOutstanding::query()->get();
        $totalBakiDebet = $loans->sum('loan_outstanding');
        if ($totalBakiDebet == 0.0) {
            return 0.0;
        }

        $nplLoans = $loans->filter(fn($loan) => !in_array($loan->loan_bi_collectability, [1, 2]));
        $totalNpl = $nplLoans->sum('loan_outstanding');

        return $totalNpl / $totalBakiDebet * 100;
    }

    public function kewajibanLancar(?string $tanggal = null): float
    {
        return $this->fincloudKewajibanLancar($tanggal);
    }

    public static function konsolidasiKewajibanLancar(?string $tanggal = null): float
    {
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
                null,
                $tanggal
            )
        );

        return $total;
    }

    public function cashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        return $this->fincloudCashRatio($tanggal, $simulated, $efektif);
    }

    public function loanToDepositRatio(?string $tanggal = null, bool $simulated = false): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $tanggal = $asOf->toDateString();

        $bakiDebet = $this->fincloudBakiDebet($tanggal); // kredit yang diberikan
        $simpanan = $this->fincloudDPKSavings($tanggal) + $this->fincloudDPKDeposito($tanggal); // total simpanan (tabungan + deposito)

        if ($simulated) {
            $proyeksiLendings = $this->proyeksiLendings()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereDate('lending_tanggal', $tanggal)
                ->sum('lending_booking_bersih');
            $bakiDebet += $proyeksiLendings;

            $proyeksiFundings = $this->proyeksiFundings()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereDate('funding_tanggal', $tanggal)
                ->sum('funding_nominal_bersih');
            $simpanan += $proyeksiFundings;
        }

        if ($simpanan == 0.0) {
            return 0.0;
        }

        return $bakiDebet / $simpanan * 100;
    }

    public static function konsolidasiCashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        return static::fincloudKonsolidasiCashRatio($tanggal, $simulated, $efektif);
    }

    public static function konsolidasiCashRatio2(?string $tanggal = null, float $totalLiquid = 0.0): float
    {
        $totalKewajibanLancar = static::konsolidasiKewajibanLancar($tanggal);
        return $totalKewajibanLancar == 0.0 ? 0.0 : ($totalLiquid / $totalKewajibanLancar * 100);
    }

    public static function konsolidasiLDR(?string $tanggal = null, bool $simulated = false): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $tanggal = $asOf->toDateString();
        $totalBakiDebet = array_sum(static::saldoNeraca2(['121'], null, $tanggal));
        if ($simulated) {
            $proyeksiLendings = ProyeksiLending::query()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereDate('lending_tanggal', $tanggal)
                ->sum('lending_booking_bersih');
            $totalBakiDebet += $proyeksiLendings;
        }
        $totalSimpanan = array_sum(static::saldoNeraca2(['221', '2312200', '2312201'], null, $tanggal));

        return $totalSimpanan == 0.0 ? 0.0 : ($totalBakiDebet / $totalSimpanan * 100);
    }

    public static function konsolidasiSaldoKas(?string $tanggal): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $asOf = $asOf->toDateString();

        $neraca = static::saldoNeraca2(['1011000'], null, $asOf);
        return array_sum($neraca);
    }

    public static function konsolidasiAssetLiquid(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $asOf = $asOf->toDateString();

        $giroTab = array_sum(static::saldoNeraca2(['111', '112'], null, $asOf));
        $kas = static::konsolidasiSaldoKas($asOf);

        $ret = $kas + $giroTab;
        if ($efektif) {
            $ret -= BranchOffice::sum('branch_saldo_aba_blokir');
        }

        if ($simulated) {
            $proyeksiCashIns = CashFlow::query()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash In'))
                ->whereDate('cash_tanggal', $asOf)
                ->sum('cash_jumlah');
            $ret += $proyeksiCashIns;

            $proyeksiCashOuts = CashFlow::query()
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

    public function fincloudSaldoNeraca(): HasMany
    {
        return $this->hasMany(SaldoNeraca::class, 'cabang', 'branch_code_fincloud');
    }

    public function fincloudSaldoKas(?string $tanggal): float
    {
        $neraca = static::saldoNeraca2(['1011000'], $this->branch_code_fincloud, $tanggal);
        return $neraca['1011000'] ?? 0.0;
    }

    public function fincloudBakiDebet(?string $tanggal): float
    {
        $neraca = static::saldoNeraca2(['121'], $this->branch_code_fincloud, $tanggal);
        return $neraca['121'] ?? 0.0;
    }

    public function fincloudKewajibanLancar(?string $tanggal): float
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

        $neraca = static::saldoNeraca2([
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
        ], $this->branch_code_fincloud, $tanggal);

        return array_sum($neraca);
    }

    public function fincloudDPKSavings(?string $tanggal): float
    {
        $neraca = static::saldoNeraca2(['221'], $this->branch_code_fincloud, $tanggal);
        return $neraca['221'] ?? 0.0;
    }

    public function fincloudDPKDeposito(?string $tanggal): float
    {
        $neraca = static::saldoNeraca2(['2312200', '2312201'], $this->branch_code_fincloud, $tanggal);
        return array_sum($neraca);
    }

    public function fincloudAbaGiro(?string $tanggal): float
    {
        $neraca = static::saldoNeraca2(['111'], $this->branch_code_fincloud, $tanggal);
        return $neraca['111'] ?? 0.0;
    }

    public function fincloudAbaTabungan(?string $tanggal): float
    {
        $neraca = static::saldoNeraca2(['112'], $this->branch_code_fincloud, $tanggal);
        return $neraca['112'] ?? 0.0;
    }

    public function fincloudAbaDeposito(?string $tanggal): float
    {
        $neraca = static::saldoNeraca2(['113'], $this->branch_code_fincloud, $tanggal);
        return $neraca['113'] ?? 0.0;
    }

    public function fincloudAssetLiquid(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $tanggal = $asOf->toDateString();

        $kas = $this->fincloudSaldoKas($tanggal);
        $giroTab = array_sum(static::saldoNeraca2(['111', '112'], $this->branch_code_fincloud, $tanggal));

        $ret = $kas + $giroTab;
        if ($efektif) {
            $ret -= $this->branch_saldo_aba_blokir;
        }

        if ($simulated) {
            $proyeksiCashIns = $this->cashFlows()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash In'))
                ->whereDate('cash_tanggal', $asOf)
                ->sum('cash_jumlah');
            $ret += $proyeksiCashIns;

            $proyeksiCashOuts = $this->cashFlows()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash Out'))
                ->whereDate('cash_tanggal', $asOf)
                ->sum('cash_jumlah');
            $ret -= $proyeksiCashOuts;
        }

        return $ret;
    }

    public function fincloudCashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        $assetLiquid = $this->fincloudAssetLiquid($tanggal, $simulated, $efektif);
        $kewajibanLancar = $this->fincloudKewajibanLancar($tanggal);
        if ($kewajibanLancar == 0.0) {
            return 0.0;
        }
        return $assetLiquid / $kewajibanLancar * 100;
    }

    public static function fincloudKonsolidasiCashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        $totalLiquid = static::konsolidasiAssetLiquid($tanggal, $simulated, $efektif);
        $totalKewajibanLancar = static::konsolidasiKewajibanLancar($tanggal);

        return $totalKewajibanLancar == 0.0 ? 0.0 : ($totalLiquid / $totalKewajibanLancar * 100);
    }
}
