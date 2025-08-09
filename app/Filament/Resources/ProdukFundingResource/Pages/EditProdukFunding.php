<?php

namespace App\Filament\Resources\ProdukFundingResource\Pages;

use App\Filament\Resources\ProdukFundingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProdukFunding extends EditRecord
{
    protected static string $resource = ProdukFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
