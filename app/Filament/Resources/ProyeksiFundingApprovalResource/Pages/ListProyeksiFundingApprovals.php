<?php

namespace App\Filament\Resources\ProyeksiFundingApprovalResource\Pages;

use App\Filament\Resources\ProyeksiFundingApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProyeksiFundingApprovals extends ListRecords
{
    protected static string $resource = ProyeksiFundingApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
