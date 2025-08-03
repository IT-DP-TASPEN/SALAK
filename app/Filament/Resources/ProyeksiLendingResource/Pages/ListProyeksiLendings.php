<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProyeksiLendings extends ListRecords
{
    protected static string $resource = ProyeksiLendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        return $tabs;
    }
}
