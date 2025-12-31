<?php

namespace App\Filament\Resources\ProyeksiFundingResource\Pages;

use App\Filament\Resources\ProyeksiFundingResource;
use App\Models\ProyeksiFunding;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateProyeksiFunding extends CreateRecord
{
    protected static string $resource = ProyeksiFundingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['funding_tanggal'] = Carbon::today()->toDateString();
        $usr = auth()->user();
        $data['funding_petugas'] = $usr->id;
        $data['funding_kantor'] = $usr->branch_office_id;

        if (isset($data['funding_deposito_jenis']) && $data['funding_deposito_jenis'] === 'Cair Tanam') {
            $nett = $data['funding_nominal'] + $data['funding_nominal_bersih'];
            $data['funding_nominal_bersih'] = $nett;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): ProyeksiFunding
    {
        $record = parent::handleRecordCreation($data);

        try {
            $submitter = auth()->user();

            User::query()
                ->where('branch_office_id', $submitter->branch_office_id)
                ->whereKeyNot($submitter->id)
                ->permission('create_proyeksi::funding::approval') // spatie scope
                ->each(function (User $user) use ($submitter, $record) {
                    $user->notify(
                        Notification::make()
                            ->title('New Proyeksi Funding Submission')
                            ->body("A new Proyeksi Funding has been submitted by {$submitter->name} and is pending your approval.")
                            ->actions([
                                Actions\Action::make('view_proyeksi_funding')
                                    ->label('View Proyeksi Funding')
                                    ->url(fn() => ProyeksiFundingResource::getUrl('view', ['record' => $record]))
                                    ->icon('heroicon-o-eye'),
                            ])
                            ->info()
                            ->toDatabase(),
                    );
                });
        } catch (\Exception $e) {
            Log::error('Error sending Proyeksi Funding approval notifications: ' . $e->getMessage());
        }

        return $record;
    }
}
