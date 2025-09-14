<?php

namespace App\Filament\Resources\CashFlowResource\Pages;

use App\Filament\Resources\CashFlowResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCashFlow extends CreateRecord
{
    protected static string $resource = CashFlowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cash_user'] = auth()->user()->id;
        $data['cash_kantor'] = auth()->user()->branchOffice->id;

        return $data;
    }
}
