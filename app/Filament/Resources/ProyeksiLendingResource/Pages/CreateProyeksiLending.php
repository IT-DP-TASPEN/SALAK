<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProyeksiLending extends CreateRecord
{
    protected static string $resource = ProyeksiLendingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['lending_agent'] = auth()->id();
        $data['lending_kantor'] = auth()->user()->branchOffice->id;
        $data['lending_booking_bersih'] = $data['lending_booking'] - ($data['lending_pelunasan_pokok'] ?? 0);

        $data['lending_tanggal_jatuh_tempo'] = Carbon::parse($data['lending_tanggal_realisasi'])
            ->addMonths((int)$data['lending_jkw'])
            ->toDateString();

        $data['lending_booking_bersih2'] = $data['lending_booking_bersih']
            - ($data['lending_pot_provisi'] ?? 0)
            - ($data['lending_pot_admin'] ?? 0)
            - ($data['lending_pot_asuransi'] ?? 0)
            - ($data['lending_pot_asuransi_extra'] ?? 0);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
