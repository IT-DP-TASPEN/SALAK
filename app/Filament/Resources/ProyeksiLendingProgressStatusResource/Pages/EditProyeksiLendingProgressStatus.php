<?php

namespace App\Filament\Resources\ProyeksiLendingProgressStatusResource\Pages;

use App\Filament\Resources\ProyeksiLendingProgressStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProyeksiLendingProgressStatus extends EditRecord
{
    protected static string $resource = ProyeksiLendingProgressStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
