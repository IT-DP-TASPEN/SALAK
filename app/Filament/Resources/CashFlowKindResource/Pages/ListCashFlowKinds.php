<?php

namespace App\Filament\Resources\CashFlowKindResource\Pages;

use App\Filament\Resources\CashFlowKindResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashFlowKinds extends ListRecords
{
    protected static string $resource = CashFlowKindResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
