<?php

namespace App\Filament\Resources\SumberPembayaranLendingResource\Pages;

use App\Filament\Resources\SumberPembayaranLendingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSumberPembayaranLendings extends ListRecords
{
    protected static string $resource = SumberPembayaranLendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
