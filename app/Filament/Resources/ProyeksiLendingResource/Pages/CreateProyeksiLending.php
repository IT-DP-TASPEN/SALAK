<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
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
        $data['lending_agent'] = auth()->id();
        $data['lending_kantor'] = auth()->user()->branchOffice->id;
        $data['lending_booking_bersih'] = $data['lending_plafond'] - ($data['lending_pelunasan_pokok'] ?? 0);

        $data['lending_tanggal_jatuh_tempo'] = Carbon::parse($data['lending_tanggal_realisasi'])
            ->addMonths((int)$data['lending_jkw'])
            ->toDateString();

        // $data['lending_angsuran_muka']

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
                (floatval($data['lending_plafond']) * (int)$data['lending_jkw'])
                + (floatval($data['lending_plafond']) * $monthly_interest_percent);
        }

        $data['lending_angsuran_muka'] = $angsuran_awal * (int)$data['lending_angsuran_muka_bulan'];
        $data['lending_saldo_tab_mengendap'] = $angsuran_awal * (int)$data['lending_saldo_tab_mengendap_bulan'];

        // remove lending_saldo_tab_mengendap_bulan and lending_angsuran_muka_bulan from data
        unset($data['lending_saldo_tab_mengendap_bulan'], $data['lending_angsuran_muka_bulan']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
