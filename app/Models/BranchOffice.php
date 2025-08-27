<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function saldoAbpTabungan(?string $tanggal = null): float
    {
        return $this->saldoNeraca(['1.240.10'], $tanggal)['1.240.10'];
    }

    public function saldoKas(?string $tanggal = null): float
    {
        return $this->saldoNeraca(['1.100'], $tanggal)['1.100'];
    }

    public function assetLiquid(?string $tanggal = null, bool $simulated = false): float
    {
        // TODO: take $tanggal into account
        $giroTab = $this
            ->penempatanABA()
            ->whereIn('aba_jenis', [10, 20]) // giro & tabungan umum
            ->sum('aba_saldo_efektif');

        $kas = $this->saldoKas($tanggal);

        $ret = $kas + $giroTab - $this->branch_saldo_aba_blokir;

        if ($simulated) {
            $proyeksiLendings = $this->proyeksiLendings()
                ->whereRaw('lending_tanggal = CURDATE()')
                ->sum('lending_booking_bersih');
            $ret -= $proyeksiLendings;

            $proyeksiFunding = $this->proyeksiFundings()
                ->whereRaw('funding_tanggal = CURDATE()')
                ->sum('funding_nominal_bersih');
            $ret += $proyeksiFunding;
        }

        return $ret;
    }

    public function kewajibanLancar(?string $tanggal = null): float
    {
        $res = $this->saldoNeraca([
            '1.200', // liabilitas segera
            '1.210', // tabungan
            '1.220' // deposito
        ], $tanggal);

        return $res['1.200'] + $res['1.210'] + $res['1.220'];
    }

    public function cashRatio(?string $tanggal = null, bool $simulated = false): float
    {
        $assetLiquid = $this->assetLiquid($tanggal, $simulated);
        $kewajibanLancar = $this->kewajibanLancar($tanggal);
        if ($kewajibanLancar === 0.0) {
            return 0.0;
        }
        return $assetLiquid / $kewajibanLancar * 100;
    }

    public function loanToDepositRatio(?string $tanggal = null, bool $simulated = false): float
    {
        // Single round-trip for all needed codes
        $res = $this->saldoNeraca(['1.130.1', '1.210', '1.220'], $tanggal);

        $bakiDebet = $res['1.130.1']; // kredit yang diberikan
        if ($simulated) {
            $proyeksiLendings = $this->proyeksiLendings()
                ->whereRaw('lending_tanggal = CURDATE()')
                ->sum('lending_booking_bersih');
            $bakiDebet += $proyeksiLendings;
        }
        $simpanan = $res['1.210'] + $res['1.220']; // total simpanan (tabungan + deposito)

        if ($simpanan === 0.0) {
            return 0.0;
        }

        return $bakiDebet / $simpanan * 100;
    }

    public static function konsolidasiCashRatio(?string $tanggal = null, bool $simulated = false): float
    {
        $totalLiquid = 0.0;
        $totalKewajibanLancar = 0.0;

        static::query()->chunkById(200, function ($branches) use ($tanggal, $simulated, &$totalLiquid, &$totalKewajibanLancar) {
            foreach ($branches as $branch) {
                $totalLiquid += $branch->assetLiquid($tanggal, $simulated);
                $totalKewajibanLancar += $branch->kewajibanLancar($tanggal);
            }
        });

        return $totalKewajibanLancar === 0.0 ? 0.0 : ($totalLiquid / $totalKewajibanLancar * 100);
    }

    public static function konsolidasiLDR(?string $tanggal = null, bool $simulated = false): float
    {
        $totalBakiDebet = 0.0;
        $totalSimpanan = 0.0;

        static::query()->chunkById(200, function ($branches) use ($tanggal, $simulated, &$totalBakiDebet, &$totalSimpanan) {
            foreach ($branches as $branch) {
                $res = $branch->saldoNeraca(['1.130.1', '1.210', '1.220'], $tanggal);
                $totalBakiDebet += $res['1.130.1'];
                if ($simulated) {
                    $proyeksiLendings = $branch->proyeksiLendings()
                        ->whereRaw('lending_tanggal = CURDATE()')
                        ->sum('lending_booking_bersih');
                    $totalBakiDebet += $proyeksiLendings;
                }
                $totalSimpanan += $res['1.210'] + $res['1.220'];
            }
        });

        return $totalSimpanan === 0.0 ? 0.0 : ($totalBakiDebet / $totalSimpanan * 100);
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
}
