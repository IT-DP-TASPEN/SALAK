<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProyeksiLending extends EditRecord
{
    protected static string $resource = ProyeksiLendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
