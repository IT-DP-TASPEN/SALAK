<?php

namespace App\Filament\Resources\ProdukFundingResource\Pages;

use App\Filament\Resources\ProdukFundingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProdukFundings extends ListRecords
{
    protected static string $resource = ProdukFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
