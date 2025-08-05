<?php

namespace App\Filament\Resources\MitraBayarResource\Pages;

use App\Filament\Resources\MitraBayarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMitraBayar extends EditRecord
{
    protected static string $resource = MitraBayarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
