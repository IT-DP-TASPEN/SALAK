<?php

namespace App\Filament\Resources\CashFlowKindResource\Pages;

use App\Filament\Resources\CashFlowKindResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashFlowKind extends EditRecord
{
    protected static string $resource = CashFlowKindResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
