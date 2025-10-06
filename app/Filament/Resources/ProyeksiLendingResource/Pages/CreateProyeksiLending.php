<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Support\ProyeksiLendingFormData;

class CreateProyeksiLending extends CreateRecord
{
    protected static string $resource = ProyeksiLendingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['lending_tanggal'] = Carbon::today()->toDateString();
        $usr = auth()->user();
        $data['lending_petugas'] = $usr->id;
        $data['lending_kantor'] = $usr->branch_office_id;

        return ProyeksiLendingFormData::calculate($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
