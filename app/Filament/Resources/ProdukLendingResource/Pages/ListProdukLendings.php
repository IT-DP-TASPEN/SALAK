<?php

namespace App\Filament\Resources\ProdukLendingResource\Pages;

use App\Filament\Resources\ProdukLendingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProdukLendings extends ListRecords
{
    protected static string $resource = ProdukLendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
