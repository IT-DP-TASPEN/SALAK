<?php

namespace App\Filament\Resources\StatusDapemResource\Pages;

use App\Filament\Resources\StatusDapemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatusDapems extends ListRecords
{
    protected static string $resource = StatusDapemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
