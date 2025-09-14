<?php

namespace App\Filament\Resources\CashFlowApprovalResource\Pages;

use App\Filament\Resources\CashFlowApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashFlowApprovals extends ListRecords
{
    protected static string $resource = CashFlowApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
