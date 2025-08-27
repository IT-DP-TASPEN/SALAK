<?php

namespace App\Filament\Resources\ProyeksiFundingResource\Pages;

use App\Filament\Resources\ProyeksiFundingResource;
use App\Models\Agent;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProyeksiFunding extends CreateRecord
{
    protected static string $resource = ProyeksiFundingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['funding_tanggal'] = Carbon::today()->toDateString();
        $agent = Agent::findOrFail($data['funding_agent']);
        $data['funding_kantor'] = $agent->branchOffice->id;

        return $data;
    }
}
