<?php

namespace App\Filament\Resources\CashFlowApprovalResource\Pages;

use App\Filament\Resources\CashFlowApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCashFlowApproval extends CreateRecord
{
    protected static string $resource = CashFlowApprovalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['approval_user'] = auth()->id();

        if ($data['approval_status'] === 'Approved') {
            $data['approval_approved_at'] = now();
            $data['approval_rejected_at'] = null;
        } elseif ($data['approval_status'] === 'Rejected') {
            $data['approval_rejected_at'] = now();
            $data['approval_approved_at'] = null;
        } else {
            $data['approval_approved_at'] = null;
            $data['approval_rejected_at'] = null;
        }

        return $data;
    }
}
