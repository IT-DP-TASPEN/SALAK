<?php

namespace App\Filament\Resources\StatusKerjaResource\Pages;

use App\Filament\Resources\StatusKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatusKerjas extends ListRecords
{
    protected static string $resource = StatusKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
