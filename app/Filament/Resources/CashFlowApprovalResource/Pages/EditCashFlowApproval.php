<?php

namespace App\Filament\Resources\CashFlowApprovalResource\Pages;

use App\Filament\Resources\CashFlowApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashFlowApproval extends EditRecord
{
    protected static string $resource = CashFlowApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
