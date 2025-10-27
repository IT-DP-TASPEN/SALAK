<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use App\Models\ProyeksiLending;
use App\Models\ProyeksiLendingProgressStatus;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewProyeksiLending extends ViewRecord
{
    protected static string $resource = ProyeksiLendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit Lending')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn(ProyeksiLending $record) => auth()->user()?->can('update_proyeksi::lending'))
                ->url(fn(ProyeksiLending $record): string => ProyeksiLendingResource::getUrl('edit', ['record' => $record])),
            Actions\Action::make('update_progress')
                ->label('Update Progress')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Select::make('progress_status')
                        ->label('Status')
                        ->options(ProyeksiLendingProgressStatus::pluck('progress_status', 'id'))
                        ->required(),
                ])
                ->action(
                    function (ProyeksiLending $record, array $data) {
                        $record->progress()->updateOrCreate(
                            [
                                'progress_lending' => $record->id,
                            ],
                            [
                                'progress_status' => $data['progress_status'],
                            ]
                        );

                        // Notify the user
                        Notification::make()
                            ->title('Status Proyeksi Lending Updated')
                            ->body("Status proyeksi lending untuk {$record->lending_nama_debitur} telah diperbarui.")
                            ->success()
                            ->send();
                    }
                )
                ->visible(fn(ProyeksiLending $record) => (
                    auth()->user()?->can('create_proyeksi::lending::progress')
                    || auth()->user()?->can('update_proyeksi::lending::progress')
                ) && $record->approval->approval_status === 'Approved'),
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->visible(
                    fn(ProyeksiLending $record) =>
                    auth()->user()?->can('create_proyeksi::lending::approval')
                        && (
                            $record->approval === null
                            || $record->approval->approval_status === 'Pending'
                        )
                )
                ->action(function (ProyeksiLending $record, array $data) {
                    $record->approval()->updateOrCreate(
                        ['approval_lending' => $record->id],
                        [
                            'approval_status' => 'Approved',
                            'approval_user' => auth()->id(),
                            'approval_approved_at' => now(),
                            'approval_comment' => $data['approval_comment'] ?? null,
                        ]
                    );
                    $record->lending_boss_application_number = $data['lending_boss_application_number'];
                    $record->save();
                    Notification::make()
                        ->title('Lending approved successfully.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('success')
                ->modalHeading('Approve Lending')
                ->modalDescription(new HtmlString('Are you sure you want to approve this lending?<br/>This action cannot be undone.'))
                ->modalSubmitActionLabel('Approve Lending')
                ->form([
                    TextInput::make('lending_boss_application_number')
                        ->label('BOSS Application Number')
                        ->placeholder('Enter BOSS Application Number')
                        ->required(),
                    Textarea::make('approval_comment')
                        ->label('Remarks')
                        ->placeholder('Optional remarks for approval')
                        ->maxLength(500),
                ]),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->visible(
                    fn(ProyeksiLending $record) =>
                    auth()->user()?->can('create_proyeksi::lending::approval')
                        && (
                            $record->approval === null
                            || $record->approval->approval_status === 'Pending'
                        )
                )
                ->action(function (ProyeksiLending $record, array $data) {
                    $record->approval()->updateOrCreate(
                        ['approval_lending' => $record->id],
                        [
                            'approval_status' => 'Rejected',
                            'approval_user' => auth()->id(),
                            'approval_rejected_at' => now(),
                            'approval_comment' => $data['approval_comment'] ?? null,
                        ]
                    );
                    Notification::make()
                        ->title('Lending rejected successfully.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('danger')
                ->modalHeading('Reject Lending')
                ->modalDescription(new HtmlString('Are you sure you want to reject this lending?<br/>This action cannot be undone.'))
                ->form([
                    Textarea::make('approval_comment')
                        ->label('Remarks')
                        ->placeholder('Optional remarks for rejection')
                        ->maxLength(500),
                ])
                ->modalSubmitActionLabel('Reject Lending'),
        ];
    }
}
