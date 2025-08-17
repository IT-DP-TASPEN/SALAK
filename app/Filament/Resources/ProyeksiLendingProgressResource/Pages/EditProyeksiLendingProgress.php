<?php

namespace App\Filament\Resources\ProyeksiLendingProgressResource\Pages;

use App\Filament\Resources\ProyeksiLendingProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProyeksiLendingProgress extends EditRecord
{
    protected static string $resource = ProyeksiLendingProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
