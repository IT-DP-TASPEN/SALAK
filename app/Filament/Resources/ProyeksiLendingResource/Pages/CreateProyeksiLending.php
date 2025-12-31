<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use App\Models\ProyeksiLending;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Support\ProyeksiLendingFormData;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateProyeksiLending extends CreateRecord
{
    protected static string $resource = ProyeksiLendingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['lending_tanggal'] = Carbon::today()->toDateString();
        $usr = auth()->user();
        $data['lending_petugas'] = $usr->id;
        $data['lending_kantor'] = $usr->branch_office_id;

        return ProyeksiLendingFormData::calculate($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): ProyeksiLending
    {
        $record = parent::handleRecordCreation($data);

        try {
            $submitter = auth()->user();

            User::query()
                ->where('branch_office_id', $submitter->branch_office_id)
                ->whereKeyNot($submitter->id)
                ->permission('create_proyeksi::lending::approval') // spatie scope
                ->each(function (User $user) use ($submitter, $record) {
                    $user->notify(
                        Notification::make()
                            ->title('New Proyeksi Lending Submission')
                            ->body("A new Proyeksi Lending has been submitted by {$submitter->name} and is pending your approval.")
                            ->actions([
                                Actions\Action::make('view_proyeksi_lending')
                                    ->label('View Proyeksi Lending')
                                    ->url(fn() => ProyeksiLendingResource::getUrl('view', ['record' => $record]))
                                    ->icon('heroicon-o-eye'),
                            ])
                            ->info()
                            ->toDatabase(),
                    );
                });
        } catch (\Exception $e) {
            Log::error('Error sending Proyeksi Lending approval notifications: ' . $e->getMessage());
        }

        return $record;
    }
}
