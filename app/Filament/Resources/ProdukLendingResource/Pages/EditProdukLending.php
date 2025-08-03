<?php

namespace App\Filament\Resources\ProdukLendingResource\Pages;

use App\Filament\Resources\ProdukLendingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProdukLending extends EditRecord
{
    protected static string $resource = ProdukLendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
