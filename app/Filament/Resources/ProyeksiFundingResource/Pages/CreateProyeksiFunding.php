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
        $usr = auth()->user();
        $data['funding_petugas'] = $usr->id;
        $data['funding_kantor'] = $usr->branch_office_id;

        if (isset($data['funding_deposito_jenis']) && $data['funding_deposito_jenis'] === 'Cair Tanam') {
            $nett = $data['funding_nominal'] - $data['funding_nominal_bersih'];
            $data['funding_nominal_bersih'] = $nett;
        }

        return $data;
    }
}
