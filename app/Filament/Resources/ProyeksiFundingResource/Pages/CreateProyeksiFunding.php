<?php

namespace App\Filament\Resources\ProyeksiFundingResource\Pages;

use App\Filament\Resources\ProyeksiFundingResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProyeksiFunding extends CreateRecord
{
    protected static string $resource = ProyeksiFundingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['funding_tanggal'] = Carbon::today()->toDateString();
        $data['funding_agent'] = auth()->id();
        $data['funding_kantor'] = auth()->user()->branchOffice->id;

        return $data;
    }
}
