<?php

namespace App\Filament\Resources\PerusahaanAsuransiResource\Pages;

use App\Filament\Resources\PerusahaanAsuransiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerusahaanAsuransis extends ListRecords
{
    protected static string $resource = PerusahaanAsuransiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
