<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProyeksiLending extends CreateRecord
{
    protected static string $resource = ProyeksiLendingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the 'lending_agent' field to the current user's ID
        $data['lending_agent'] = auth()->id();

        // Set the 'lending_kantor' field to the current user's branch office ID
        $data['lending_kantor'] = auth()->user()->branchOffice->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
