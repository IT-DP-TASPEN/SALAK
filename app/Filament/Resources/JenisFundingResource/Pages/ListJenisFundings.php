<?php

namespace App\Filament\Resources\JenisFundingResource\Pages;

use App\Filament\Resources\JenisFundingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisFundings extends ListRecords
{
    protected static string $resource = JenisFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
