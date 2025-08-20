<?php

namespace App\Filament\Resources\ProyeksiFundingResource\Pages;

use App\Filament\Resources\ProyeksiFundingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\ProyeksiFunding;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ViewProyeksiFunding extends ViewRecord
{
    protected static string $resource = ProyeksiFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit Funding')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn(ProyeksiFunding $record) => auth()->user()?->can('update_proyeksifunding'))
                ->url(fn(ProyeksiFunding $record): string => ProyeksiFundingResource::getUrl('edit', ['record' => $record])),
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->visible(
                    fn(ProyeksiFunding $record) =>
                    auth()->user()?->can('create_proyeksifundingapproval')
                        && (
                            $record->approval === null
                            || $record->approval->approval_status === 'Pending'
                        )
                )
                ->action(function (ProyeksiFunding $record, array $data) {
                    $record->approval()->updateOrCreate(
                        ['approval_funding' => $record->id],
                        [
                            'approval_status' => 'Approved',
                            'approval_user' => auth()->id(),
                            'approval_approved_at' => now(),
                            'approval_comment' => $data['approval_comment'] ?? null,
                        ]
                    );
                    Notification::make()
                        ->title('Funding approved successfully.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('success')
                ->modalHeading('Approve Funding')
                ->modalDescription(new HtmlString('Are you sure you want to approve this Funding?<br/>This action cannot be undone.'))
                ->modalSubmitActionLabel('Approve Funding')
                ->form([
                    Textarea::make('approval_comment')
                        ->label('Remarks')
                        ->placeholder('Optional remarks for approval')
                        ->maxLength(500),
                ]),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->visible(
                    fn(ProyeksiFunding $record) =>
                    auth()->user()?->can('create_proyeksifundingapproval')
                        && (
                            $record->approval === null
                            || $record->approval->approval_status === 'Pending'
                        )
                )
                ->action(function (ProyeksiFunding $record, array $data) {
                    $record->approval()->updateOrCreate(
                        ['approval_funding' => $record->id],
                        [
                            'approval_status' => 'Rejected',
                            'approval_user' => auth()->id(),
                            'approval_rejected_at' => now(),
                            'approval_comment' => $data['approval_comment'] ?? null,
                        ]
                    );
                    Notification::make()
                        ->title('Funding rejected successfully.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('danger')
                ->modalHeading('Reject Funding')
                ->modalDescription(new HtmlString('Are you sure you want to reject this Funding?<br/>This action cannot be undone.'))
                ->form([
                    Textarea::make('approval_comment')
                        ->label('Remarks')
                        ->placeholder('Optional remarks for rejection')
                        ->maxLength(500),
                ])
                ->modalSubmitActionLabel('Reject Funding'),
        ];
    }
}
