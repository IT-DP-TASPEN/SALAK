<?php

namespace App\Filament\Resources\SumberPembayaranLendingResource\Pages;

use App\Filament\Resources\SumberPembayaranLendingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSumberPembayaranLending extends EditRecord
{
    protected static string $resource = SumberPembayaranLendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
