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

    public function penempatanABA(): HasMany
    {
        return $this->hasMany(DataAbaMaster::class, 'aba_kantor', 'branch_code');
    }

    public function transaksiABA(): HasMany
    {
        return $this->hasMany(DataAbaTrans::class, 'trans_kantor', 'branch_code');
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class, 'cash_kantor', 'id');
    }

    public function saldoAbpTabungan(?string $tanggal = null): float
    {
        // return static::saldoNeraca2(['1.240.10'], $this->branch_code, $tanggal)['1.240.10'];
        return $this->fincloudAbpTabungan($tanggal);
    }

    public function saldoKas(?string $tanggal = null): float
    {
        // return static::saldoNeraca2(['1.100'], $this->branch_code, $tanggal)['1.100'];
        return $this->fincloudSaldoKas($tanggal);
    }

    public static function saldoAba(?string $tanggal = null, ?string $branch = null, array $jenis = []): float
    {
        // static $saldoCache = [];

        // $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        // $dateForCalc = $asOf->gte(Carbon::today()) ?
        //     Carbon::yesterday()->toDateString() :
        //     $asOf->toDateString();

        // $cacheKey = md5($branch . '|' . $dateForCalc . '|' . implode(',', $jenis));
        // if (!isset($saldoCache[$cacheKey])) {
        //     $saldo = DB::connection('mso-backup')
        //         ->table('data_aba_master')
        //         ->when($jenis, fn($q) => $q->whereIn('aba_jenis', $jenis))
        //         ->when($branch, fn($q) => $q->where('aba_kantor', $branch))
        //         ->selectRaw(
        //             'SUM(HitungAbaSaldoEfektif(aba_kode, ?)) AS saldo',
        //             [$dateForCalc]
        //         )
        //         ->value('saldo') ?? 0;

        //     if ($asOf->isToday()) {
        //         $saldo += DB::connection('mso')
        //             ->table('data_aba_trans')
        //             ->join('data_aba_master', 'aba_kode', '=', 'trans_rekening')
        //             ->when($jenis, fn($q) => $q->whereIn('aba_jenis', $jenis))
        //             ->when($branch, fn($q) => $q->where('aba_kantor', $branch))
        //             ->where('trans_status', 1) // VALIDASI
        //             ->whereDate('trans_reg_date', $asOf->toDateString())
        //             ->selectRaw('COALESCE(SUM(trans_kredit - trans_debet), 0) AS saldo')
        //             ->value('saldo') ?? 0;
        //     }

        //     $saldoCache[$cacheKey] = $saldo;
        // }

        // return $saldoCache[$cacheKey];
        $branch = static::where('branch_code', $branch)
            ->orWhere('branch_code_fincloud', $branch)
            ->first();
        if (!$branch) {
            return 0.0;
        }
        return 0.0;
    }

    public function assetLiquid(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        // $giroTab = static::saldoAba($asOf, $this->branch_code, [10, 20]); // giro & tabungan umum
        $giroTab = $this->fincloudAbaGiro($asOf->toDateString()) + $this->fincloudAbaTabungan($asOf->toDateString());
        $kas = $this->saldoKas($tanggal);

        $ret = $kas + $giroTab - ($efektif ? $this->branch_saldo_aba_blokir : 0.0);

        if ($simulated) {
            $tomorrow = $asOf->copy()->addDay();
            $proyeksiCashIns = $this->cashFlows()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash In'))
                ->whereDate('cash_tanggal', $tomorrow)
                ->sum('cash_jumlah');
            $ret += $proyeksiCashIns;

            $proyeksiCashOuts = $this->cashFlows()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash Out'))
                ->whereDate('cash_tanggal', $tomorrow)
                ->sum('cash_jumlah');
            $ret -= $proyeksiCashOuts;
        }

        return $ret;
    }

    public function npl(): float
    {
        $kolek = Cache::get('dashboard:kolek', []);
        $totalBakiDebet = array_sum(array_column($kolek, 'baki_debet'));
        $npl = array_filter(
            $kolek,
            fn($item) =>
            !in_array($item->kolek, ['L', 'DP']) && $item->kantor === $this->branch_code
        );
        $totalNPL = array_sum(array_column($npl, 'baki_debet'));
        if ($totalBakiDebet === 0) {
            return 0.0;
        }

        return $totalNPL / $totalBakiDebet * 100;
    }

    public static function konsolidasiNPL(): float
    {
        $kolek = Cache::get('dashboard:kolek', []);
        $totalBakiDebet = array_sum(array_column($kolek, 'baki_debet'));
        $npl = array_filter(
            $kolek,
            fn($item) =>
            !in_array($item->kolek, ['L', 'DP'])
        );
        $totalNPL = array_sum(array_column($npl, 'baki_debet'));
        if ($totalBakiDebet === 0) {
            return 0.0;
        }

        return $totalNPL / $totalBakiDebet * 100;
    }

    public function kewajibanLancar(?string $tanggal = null): float
    {
        // $res = static::saldoNeraca2(
        //     [
        //         '1.200', // liabilitas segera
        //         '1.210', // tabungan
        //         '1.220', // deposito
        //     ],
        //     $this->branch_code,
        //     $tanggal,
        // );

        // return $res['1.200'] + $res['1.210'] + $res['1.220'];
        return $this->fincloudKewajibanLancar($tanggal);
    }

    public static function konsolidasiKewajibanLancar(?string $tanggal = null): float
    {
        $total = 0.0;

        // collect(static::saldoNeraca2(['1.200', '1.210', '1.220'], null, $tanggal))
        //     ->each(function ($saldo) use (&$total) {
        //         $total += $saldo;
        //     });

        static::query()->chunkById(200, function ($branches) use ($tanggal, &$total) {
            foreach ($branches as $branch) {
                $total += $branch->kewajibanLancar($tanggal);
            }
        });

        return $total;
    }

    public function cashRatio(?string $tanggal = null, bool $simulated = false): float
    {
        $assetLiquid = $this->fincloudAssetLiquid($tanggal, $simulated);
        $kewajibanLancar = $this->fincloudKewajibanLancar($tanggal);
        if ($kewajibanLancar === 0.0) {
            return 0.0;
        }

        return $assetLiquid / $kewajibanLancar * 100;
    }

    public function loanToDepositRatio(?string $tanggal = null, bool $simulated = false): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $tanggal = $asOf->toDateString();
        // Single round-trip for all needed codes
        $res = static::saldoNeraca2(['1.130.1', '1.210', '1.220'], $this->branch_code, $tanggal);

        $bakiDebet = $res['1.130.1']; // kredit yang diberikan
        $simpanan = $res['1.210'] + $res['1.220']; // total simpanan (tabungan + deposito)

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

        if ($simpanan === 0.0) {
            return 0.0;
        }

        return $bakiDebet / $simpanan * 100;
    }

    public static function konsolidasiCashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        // $totalLiquid = static::konsolidasiAssetLiquid($tanggal, $simulated, $efektif);
        // $totalKewajibanLancar = static::konsolidasiKewajibanLancar($tanggal);

        // return $totalKewajibanLancar === 0.0 ? 0.0 : ($totalLiquid / $totalKewajibanLancar * 100);
        return static::fincloudKonsolidasiCashRatio($tanggal, $simulated, $efektif);
    }

    public static function konsolidasiCashRatio2(?string $tanggal = null, float $totalLiquid = 0.0): float
    {
        $totalKewajibanLancar = static::konsolidasiKewajibanLancar($tanggal);

        return $totalKewajibanLancar === 0.0 ? 0.0 : ($totalLiquid / $totalKewajibanLancar * 100);
    }

    public static function konsolidasiLDR(?string $tanggal = null, bool $simulated = false): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $tanggal = $asOf->toDateString();
        $totalBakiDebet = array_sum(static::saldoNeraca2(['1.130.1'], null, $tanggal));
        if ($simulated) {
            $proyeksiLendings = ProyeksiLending::query()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereDate('lending_tanggal', $tanggal)
                ->sum('lending_booking_bersih');
            $totalBakiDebet += $proyeksiLendings;
        }
        $totalSimpanan = array_sum(static::saldoNeraca2(['1.210', '1.220'], null, $tanggal));

        return $totalSimpanan === 0.0 ? 0.0 : ($totalBakiDebet / $totalSimpanan * 100);
    }

    public static function konsolidasiSaldoKas(?string $tanggal)
    {
        $neraca = BranchOffice::saldoNeraca2(['1.100'], null, $tanggal);

        return array_sum($neraca);
    }

    public static function konsolidasiAssetLiquid(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $asOf = $asOf->toDateString();

        $giroTab = 0.0;
        $kas = 0.0;

        static::chunkById(200, function ($branches) use (&$giroTab, &$kas, $asOf, $efektif) {
            foreach ($branches as $branch) {
                $giroTab += $branch->fincloudAbaGiro($asOf) + $branch->fincloudAbaTabungan($asOf);
                $kas += $branch->fincloudSaldoKas($asOf);
                if ($efektif) {
                    $giroTab -= $branch->branch_saldo_aba_blokir;
                }
            }
        });

        $ret = $kas + $giroTab;

        // $giroTab = static::saldoAba($asOf, null, [10, 20]); // giro & tabungan umum
        // $kas = self::konsolidasiSaldoKas($tanggal);

        // $ret = $kas + $giroTab;
        // if ($efektif) {
        //     $ret -= BranchOffice::sum('branch_saldo_aba_blokir');
        // }

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

    public function saldoNeraca(array $kodePerkiraanList, ?string $tanggal = null): array
    {
        $tanggal ??= Carbon::today()->toDateString();

        /**
         * SELECT
         *   perk_text,
         *   sandi_text,
         *   perk_kode,
         *   sandi_pos,
         *   FORMAT(
         *     IF (
         *       (perk_d_or_k = 'D'),
         *       SUM(neraca_debet) - SUM(neraca_kredit),
         *       SUM(neraca_kredit) - SUM(neraca_debet)
         *     ),
         *     2
         *   ) AS saldo
         * FROM
         *   kode_perkiraan
         * JOIN
         *   kode_perksandi ON sandip_mbs = perk_kode
         * JOIN
         *   kode_sandipos ON sandi_pos = sandip_pos
         * JOIN
         *   data_akuntansi_neraca ON neraca_perkiraan = perk_kode
         * WHERE
         *   neraca_kantor = '01'
         *   AND neraca_tanggal <= '2025-08-08'
         * GROUP BY
         *   perk_kode, sandi_pos
         * ORDER BY
         *   perk_kode;
         */
        $rows = DB::connection('mso')
            ->table('kode_perkiraan')
            ->join('kode_perksandi', 'sandip_mbs', '=', 'perk_kode')
            ->join('kode_sandipos', 'sandi_pos', '=', 'sandip_pos')
            ->join('data_akuntansi_neraca', 'neraca_perkiraan', '=', 'perk_kode')
            ->select(
                'perk_kode',
                DB::raw("
                    SUM(
                        CASE
                            WHEN perk_d_or_k = 'D'
                            THEN neraca_debet - neraca_kredit
                            ELSE neraca_kredit - neraca_debet
                        END
                    ) AS saldo
                ")
            )
            ->where('neraca_kantor', $this->branch_code)
            ->whereIn('perk_kode', $kodePerkiraanList)
            ->where('neraca_tanggal', '<=', $tanggal)
            ->groupBy('sandip_pos')
            ->pluck('saldo', 'perk_kode');

        return $rows->toArray();
    }

    public static function saldoNeraca2(array $kodePerkiraanList, ?string $branchCode = null, ?string $tanggal = null): array
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $rows = DB::connection('mso')
            ->table('kode_perkiraan')
            ->join('kode_perksandi', 'sandip_mbs', '=', 'perk_kode')
            ->join('kode_sandipos', 'sandi_pos', '=', 'sandip_pos')
            ->join('data_akuntansi_neraca', 'neraca_perkiraan', '=', 'perk_kode')
            ->select(
                'perk_kode',
                DB::raw(
                    'SUM(
                    CASE
                        WHEN perk_d_or_k = \'D\'
                        THEN neraca_debet - neraca_kredit
                        ELSE neraca_kredit - neraca_debet
                    END
                ) AS saldo'
                )
            )
            ->when($branchCode, fn($q) => $q->where('neraca_kantor', $branchCode))
            ->whereIn('perk_kode', $kodePerkiraanList)
            ->where('neraca_tanggal', '<=', $asOf->toDateString())
            ->groupBy('sandip_pos')
            ->pluck('saldo', 'perk_kode');

        return $rows->toArray();
    }

    public function fincloudSaldoNeraca(): HasMany
    {
        return $this->hasMany(SaldoNeraca::class, 'cabang', 'branch_code_fincloud');
    }

    public function fincloudSaldoKas(?string $tanggal): float
    {
        $neraca = $this
            ->fincloudSaldoNeraca()
            ->where('noakun', '1011000') // cash
            ->where('tanggal', $tanggal ?? Carbon::today()->toDateString())
            ->first();

        return $neraca->saldoakhir ?? 0.0;
    }

    public function fincloudBakiDebet(?string $tanggal): float
    {
        $neraca = $this
            ->fincloudSaldoNeraca()
            ->where('noakun', '121') // loan receivable
            ->where('tanggal', $tanggal ?? Carbon::today()->toDateString())
            ->first();

        return $neraca->saldoakhir ?? 0.0;
    }

    public function fincloudKewajibanLancar(?string $tanggal): float
    {
        $neraca = $this
            ->fincloudSaldoNeraca()
            ->whereIn('noakun', [
                '210',     // Tax Payable
                '2011008', // Deposit - Interest Due
                '2011001', // Deposit - Third Parties
                '2011004', // Debtor Customer Deposit / Temporary
                '2011005', // Taspen Pension Customer Deposits
                '2011006', // Deposits for Retired Civil Servants
                '2011007', // DP Taspen Pension Deposit
                '2081002', // Deposit - Premium Deposit
                '2081003', // Deposit - Non-formal BPJSTK Premium for BPR DP TASPEN Customers
                '2011009', // Other Deposits - Credit Marketing Fee
                '2011010', // Other Deposits - Others
                '2212101', // Savings Saving Account - main
                '2212102', // Savings Pension ASN
                '2212103', // Savings Pension Taspen
                '2212104', // Savings Simpel
                '2212105', // Savings Friend
                '2212106', // Savings SISETO
                '2212107', // Savings IBADAH
                '2212108', // Savings QURBAN
                '2212109', // Savings TOUR
                '2212110', // Savings EMAS
                '2312200', // Time Deposit 01
                '2312201', // Time Deposit 02
            ])
            ->where('tanggal', $tanggal ?? Carbon::today()->toDateString())
            ->get();

        return $neraca->sum('saldoakhir');
    }

    public function fincloudAbaGiro(?string $tanggal): float
    {
        $neraca = $this
            ->fincloudSaldoNeraca()
            ->whereIn('noakun', [
                '1111010', // Current Account BCA
                '1111011', // Current Account Danamon
                '1111012', // Current Account Mandiri
                '1111013', // Current Account Permata
                '1111014', // Current Account BPD Banten
                '1111015', // Current Account Mayapada
                '1111016', // Current Account J - trust
                '1111018', // Current Account Permata BTB
                '1111019', // Current Account BNI
                '1111020', // Current Account BRI
                '1111021', // Current Account BPD Jabar
                '1111022', // Current Account BRI
                '1111023', // Current Account Mandiri Taspen
                '1111024', // Current Account Mandiri Taspen
                '1111025', // Current Account BPR Jabar
                '1111026', // Current Account BWS
                '1111027', // Current Account DKI
                '1111028', // Current Account BSI
                '1111029', // Current Account BTN
                '1111030', // Current Account Maybank Indonesia
                '1111031', // Current Account Aladin Syariah
                '1111032', // Current Account Maybank Indonesia
                '1111033', // Current Account BRI
                '1111034', // Current Account Niaga
                '1111035', // Current Account Mandiri
                '1111036', // Current Account Mandiri
                '1111037', // Current Account BRI
                '1111038', // Current Account BRI
                '1111039', // Current Account Mandiri
                '1111040', // Current Account Mandiri
                '1111041', // Current Account BRI
                '1111042', // Current Account Mandiri
                '1111043', // Current Account BRI
                '1111044', // Current Account Mandiri
                '1111045', // Current Account BRI
                '1111046', // Current Account BPD Jabar
                '1111047', // Current Account BPD Jabar
                '1111048', // Current Account BRI
            ])
            ->where('tanggal', $tanggal ?? Carbon::today()->toDateString())
            ->get();

        return $neraca->sum('saldoakhir');
    }

    public function fincloudAbaTabungan(?string $tanggal): float
    {
        $neraca = $this
            ->fincloudSaldoNeraca()
            ->whereIn('noakun', [
                '1121100', // Savings Account BCA
                '1121101', // Savings Account Danamon
                '1121102', // Savings Account Mandiri
                '1121103', // Savings Account Permata
                '1121104', // Savings Account BPD Banten
                '1121105', // Savings Account Mayapada
                '1121106', // Savings Account J-trust
                '1121107', // Savings Account Perkreditan Rakyat Karyajatnika Sadaya
                '1121108', // Savings Account Permata BTB
                '1121109', // Savings Account PT BPRS Mulia Berkah Abadi
                '1121110', // Savings Account PT BPR Lestari Bali
                '1121111', // Savings Account PT BPR Ulima Djumpa Marom
                '1121112', // Savings Account BRI
            ])
            ->where('tanggal', $tanggal ?? Carbon::today()->toDateString())
            ->get();

        return $neraca->sum('saldoakhir');
    }

    public function fincloudAbpTabungan(?string $tanggal = null): float
    {
        $neraca = $this
            ->fincloudSaldoNeraca()
            ->where('noakun', '2212111') // ABP Tabungan
            ->where('tanggal', $tanggal ?? Carbon::today()->toDateString())
            ->first();

        return $neraca->saldoakhir ?? 0.0;
    }

    public function fincloudAssetLiquid(?string $tanggal = null, bool $simulated = false, bool $efektif = true): float
    {
        $asOf = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $tanggal = $asOf->toDateString();
        $kas = $this->fincloudSaldoKas($tanggal);
        $abaGiro = $this->fincloudAbaGiro($tanggal);
        $abaTabungan = $this->fincloudAbaTabungan($tanggal);

        $ret = $kas + $abaGiro + $abaTabungan;
        if ($efektif) {
            $ret -= $this->branch_saldo_aba_blokir;
        }

        if ($simulated) {
            $proyeksiLendings = $this->proyeksiLendings()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereDate('lending_tanggal', $tanggal)
                ->sum('lending_booking_bersih');
            $ret -= $proyeksiLendings;

            $proyeksiFunding = $this->proyeksiFundings()
                ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                ->whereDate('funding_tanggal', $tanggal)
                ->sum('funding_nominal_bersih');
            $ret += $proyeksiFunding;
        }

        return $ret;
    }

    public function fincloudCashRatio(?string $tanggal = null, bool $simulated = false): float
    {
        $assetLiquid = $this->fincloudAssetLiquid($tanggal, $simulated);
        $kewajibanLancar = $this->fincloudKewajibanLancar($tanggal);
        if ($kewajibanLancar === 0.0) {
            return 0.0;
        }

        return $assetLiquid / $kewajibanLancar * 100;
    }

    public static function fincloudKonsolidasiCashRatio(?string $tanggal = null, bool $simulated = false): float
    {
        $totalLiquid = 0.0;
        $totalKewajibanLancar = 0.0;

        static::query()->chunkById(200, function ($branches) use ($tanggal, $simulated, &$totalLiquid, &$totalKewajibanLancar) {
            foreach ($branches as $branch) {
                $totalLiquid += $branch->fincloudAssetLiquid($tanggal, $simulated);
                $totalKewajibanLancar += $branch->fincloudKewajibanLancar($tanggal);
            }
        });

        return $totalKewajibanLancar === 0.0 ? 0.0 : ($totalLiquid / $totalKewajibanLancar * 100);
    }

    // public static function fincloudBak
}
