<?php

namespace App\Filament\Resources\ProyeksiFundingApprovalResource\Pages;

use App\Filament\Resources\ProyeksiFundingApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProyeksiFundingApproval extends CreateRecord
{
    protected static string $resource = ProyeksiFundingApprovalResource::class;

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
