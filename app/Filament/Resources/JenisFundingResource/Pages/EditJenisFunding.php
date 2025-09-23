<?php

namespace App\Filament\Resources\JenisFundingResource\Pages;

use App\Filament\Resources\JenisFundingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenisFunding extends EditRecord
{
    protected static string $resource = JenisFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
