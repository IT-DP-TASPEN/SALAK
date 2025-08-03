<?php

namespace App\Filament\Resources\StatusDapemResource\Pages;

use App\Filament\Resources\StatusDapemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatusDapem extends EditRecord
{
    protected static string $resource = StatusDapemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
