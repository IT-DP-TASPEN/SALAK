<?php

namespace App\Filament\Resources\ProyeksiLendingApprovalResource\Pages;

use App\Filament\Resources\ProyeksiLendingApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProyeksiLendingApprovals extends ListRecords
{
    protected static string $resource = ProyeksiLendingApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
