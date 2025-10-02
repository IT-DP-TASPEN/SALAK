<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use App\Models\Agent;
use App\Models\ProdukLending;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProyeksiLending extends CreateRecord
{
    protected static string $resource = ProyeksiLendingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $produk = ProdukLending::find($data['lending_produk'])?->produk_nama ?? '';

        $data['lending_tanggal'] = Carbon::today()->toDateString();
        $usr = auth()->user();
        $data['lending_petugas'] = $usr->id;
        $data['lending_kantor'] = $usr->branch_office_id;

        $data['lending_tanggal_jatuh_tempo'] = Carbon::parse($data['lending_tanggal_realisasi'])
            ->addMonths((int)$data['lending_jkw'])
            ->toDateString();

        $monthly_interest_percent = $data['lending_bunga_percent'] / 12 / 100;
        $angsuran_awal = 0;
        if ($data['lending_sistem_bunga'] === 'Anuitas') {
            $pangkat = pow(1 + $monthly_interest_percent, (int)$data['lending_jkw']);
            $angsuran = $data['lending_plafond'] * ($monthly_interest_percent * $pangkat) / ($pangkat - 1);
            $angsuran_awal = floor($angsuran);
        } elseif ($produk === 'DISKONTO') {
            $angsuran_awal = ceil($data['lending_plafond'] * $monthly_interest_percent);
            $data['lending_bunga_muka'] = $data['lending_plafond'] * $monthly_interest_percent * $data['lending_jkw'];
        } elseif ($data['lending_sistem_bunga'] === 'Flate') {
            $angsuran_awal =
                (floatval($data['lending_plafond']) / (int)$data['lending_jkw'])
                + (floatval($data['lending_plafond']) * $monthly_interest_percent);
            $angsuran_awal = floor($angsuran_awal);
        }

        $data['lending_angsuran_muka'] = $angsuran_awal * (int)$data['lending_angsuran_muka_bulan'];
        $data['lending_saldo_tab_mengendap'] = $angsuran_awal * (int)$data['lending_saldo_tab_mengendap_bulan'];
        $data['lending_booking_bersih'] = $data['lending_plafond']
            - $data['lending_pot_provisi']
            - $data['lending_pot_admin']
            - $data['lending_pot_premi']
            - $data['lending_pot_premi_extra']
            - ($data['lending_bundling_bpjs'] ?? 0)
            - ($data['lending_bunga_muka'] ?? 0)
            - ($data['lending_saldo_tab_mengendap'] ?? 0)
            - ($data['lending_angsuran_muka'] ?? 0)
            - ($data['lending_nominal_pelunasan_takeover'] ?? 0)
            - ($data['lending_pelunasan_pokok'] ?? 0)
            - ($data['lending_pelunasan_bunga'] ?? 0);

        $data['lending_angsuran_fasilitas_aktif'] = $data['lending_angsuran_fasilitas_aktif'] ?? [];
        $data['lending_angsuran_fasilitas_aktif'][] = [
            'lending_nominal_angsuran' => $angsuran_awal,
        ];
        $totalAngsuran = array_sum(
            array_map(
                fn($item) => floatval($item['lending_nominal_angsuran'] ?? 0),
                $data['lending_angsuran_fasilitas_aktif']
            )
        );

        $gaji_bersih = floatval($data['lending_gaji_bersih'] ?? 0);
        $data['lending_dsr'] = $gaji_bersih > 0 ? ($totalAngsuran / $gaji_bersih) * 100 : 0;
        $data['lending_dsr'] = round($data['lending_dsr'], 2);

        // remove lending_saldo_tab_mengendap_bulan and lending_angsuran_muka_bulan from data
        unset($data['lending_saldo_tab_mengendap_bulan'], $data['lending_angsuran_muka_bulan'], $data['lending_with_bpjs']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
