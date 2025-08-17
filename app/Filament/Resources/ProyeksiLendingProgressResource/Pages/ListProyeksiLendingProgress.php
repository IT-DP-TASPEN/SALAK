<?php

namespace App\Filament\Resources\ProyeksiLendingProgressResource\Pages;

use App\Filament\Resources\ProyeksiLendingProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProyeksiLendingProgress extends ListRecords
{
    protected static string $resource = ProyeksiLendingProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
