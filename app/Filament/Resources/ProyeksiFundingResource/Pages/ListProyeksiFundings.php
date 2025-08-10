<?php

namespace App\Filament\Resources\ProyeksiFundingResource\Pages;

use App\Filament\Resources\ProyeksiFundingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProyeksiFundings extends ListRecords
{
    protected static string $resource = ProyeksiFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
