<?php

namespace App\Filament\Resources\CashFlowResource\Pages;

use App\Filament\Resources\CashFlowResource;
use App\Models\CashFlow;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewCashFlow extends ViewRecord
{
    protected static string $resource = CashFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit Cash Flow')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn(CashFlow $record) => auth()->user()?->can('update_cash::flow'))
                ->url(fn(CashFlow $record): string => CashFlowResource::getUrl('edit', ['record' => $record])),
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->visible(
                    fn(CashFlow $record) =>
                    auth()->user()?->can('create_cash::flow::approval')
                        && (
                            $record->approval === null
                            || $record->approval->approval_status === 'Pending'
                        )
                )
                ->action(function (CashFlow $record, array $data) {
                    $record->approval()->updateOrCreate(
                        ['approval_cash_flow' => $record->id],
                        [
                            'approval_status' => 'Approved',
                            'approval_user' => auth()->id(),
                            'approval_approved_at' => now(),
                            'approval_comment' => $data['approval_comment'] ?? null,
                        ]
                    );
                    Notification::make()
                        ->title('Cash flow approved successfully.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('success')
                ->modalHeading('Approve Cash Flow')
                ->modalDescription(new HtmlString('Are you sure you want to approve this Cash flow?<br/>This action cannot be undone.'))
                ->modalSubmitActionLabel('Approve Cash Flow')
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
                    fn(CashFlow $record) =>
                    auth()->user()?->can('create_cash::flow::approval')
                        && (
                            $record->approval === null
                            || $record->approval->approval_status === 'Pending'
                        )
                )
                ->action(function (CashFlow $record, array $data) {
                    $record->approval()->updateOrCreate(
                        ['approval_cash_flow' => $record->id],
                        [
                            'approval_status' => 'Rejected',
                            'approval_user' => auth()->id(),
                            'approval_rejected_at' => now(),
                            'approval_comment' => $data['approval_comment'] ?? null,
                        ]
                    );
                    Notification::make()
                        ->title('Cash flow rejected successfully.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('danger')
                ->modalHeading('Reject Cash Flow')
                ->modalDescription(new HtmlString('Are you sure you want to reject this Cash flow?<br/>This action cannot be undone.'))
                ->form([
                    Textarea::make('approval_comment')
                        ->label('Remarks')
                        ->placeholder('Optional remarks for rejection')
                        ->maxLength(500),
                ])
                ->modalSubmitActionLabel('Reject Cash Flow'),
        ];
    }
}
