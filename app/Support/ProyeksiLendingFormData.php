<?php

namespace App\Support;

use App\Models\ProdukLending;
use Carbon\Carbon;

class ProyeksiLendingFormData
{
    public static function calculate(array $data): array
    {
        $tenor = (int) ($data['lending_jkw'] ?? 0);

        $data['lending_tanggal_jatuh_tempo'] = Carbon::parse($data['lending_tanggal_realisasi'])
            ->addMonths($tenor)
            ->toDateString();

        $monthlyRate = floatval($data['lending_bunga_percent'] ?? 0) / 12 / 100;
        $plafond = floatval($data['lending_plafond'] ?? 0);
        $angsuranAwal = 0.0;

        if ($data['lending_sistem_bunga'] === 'Anuitas' && $monthlyRate > 0 && $tenor > 0) {
            $pow = pow(1 + $monthlyRate, $tenor);
            $denominator = $pow - 1;
            if ($denominator != 0.0) {
                $angsuran = $plafond * (($monthlyRate * $pow) / $denominator);
                $angsuranAwal = floor($angsuran);
            }
        } elseif (ProdukLending::find($data['lending_produk'] ?? null)?->produk_nama === 'DISKONTO') {
            $angsuranAwal = ceil($plafond * $monthlyRate);
            $data['lending_bunga_muka'] = $plafond * $monthlyRate * $tenor;
        } elseif ($data['lending_sistem_bunga'] === 'Flate' && $tenor > 0) {
            $angsuran = ($plafond / $tenor) + ($plafond * $monthlyRate);
            $angsuranAwal = floor($angsuran);
        }

        $angsuranMukaBulan = (int) ($data['lending_angsuran_muka_bulan'] ?? 0);
        $saldoMengendapBulan = (int) ($data['lending_saldo_tab_mengendap_bulan'] ?? 0);

        $data['lending_angsuran_muka'] = $angsuranAwal * $angsuranMukaBulan;
        $data['lending_saldo_tab_mengendap'] = $angsuranAwal * $saldoMengendapBulan;

        $data['lending_booking_bersih'] = $plafond
            - floatval($data['lending_pot_provisi'] ?? 0)
            - floatval($data['lending_pot_admin'] ?? 0)
            - floatval($data['lending_pot_premi'] ?? 0)
            - floatval($data['lending_pot_premi_extra'] ?? 0)
            - floatval($data['lending_bundling_bpjs'] ?? 0)
            - floatval($data['lending_bunga_muka'] ?? 0)
            - floatval($data['lending_saldo_tab_mengendap'] ?? 0)
            - floatval($data['lending_angsuran_muka'] ?? 0)
            - floatval($data['lending_nominal_pelunasan_takeover'] ?? 0)
            - floatval($data['lending_pelunasan_pokok'] ?? 0)
            - floatval($data['lending_pelunasan_bunga'] ?? 0);

        $data['lending_angsuran_fasilitas_aktif'] ??= [];
        $data['lending_angsuran_fasilitas_aktif'][] = [
            'lending_nominal_angsuran' => $angsuranAwal,
        ];
        // $data['lending_angsuran_fasilitas_aktif'] = $existingInstallments;

        $totalInstallment = array_sum(
            array_map(
                static fn($item) => floatval($item['lending_nominal_angsuran'] ?? 0),
                $data['lending_angsuran_fasilitas_aktif']
            )
        );

        $netIncome = floatval($data['lending_gaji_bersih'] ?? 0);
        $data['lending_dsr'] = $netIncome > 0 ? round(($totalInstallment / $netIncome) * 100, 2) : 0;

        unset($data['lending_saldo_tab_mengendap_bulan'], $data['lending_angsuran_muka_bulan'], $data['lending_with_bpjs']);

        return $data;
    }
}
