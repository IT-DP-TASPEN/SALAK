<?php

namespace App\Filament\Resources\ProyeksiLendingProgressStatusResource\Pages;

use App\Filament\Resources\ProyeksiLendingProgressStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProyeksiLendingProgressStatuses extends ListRecords
{
    protected static string $resource = ProyeksiLendingProgressStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
