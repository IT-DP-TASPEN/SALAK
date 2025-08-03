<?php

namespace App\Filament\Resources\StatusKerjaResource\Pages;

use App\Filament\Resources\StatusKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatusKerja extends EditRecord
{
    protected static string $resource = StatusKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
