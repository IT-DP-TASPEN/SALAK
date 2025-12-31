<?php

namespace App\Filament\Resources\CashFlowResource\Pages;

use App\Filament\Resources\CashFlowResource;
use App\Models\CashFlow;
use App\Models\User;
use Filament\Notifications\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateCashFlow extends CreateRecord
{
    protected static string $resource = CashFlowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cash_user'] = auth()->user()->id;
        $data['cash_kantor'] = auth()->user()->branchOffice->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): CashFlow
    {
        $record = parent::handleRecordCreation($data);

        try {
            $submitter = auth()->user();

            User::query()
                ->where('branch_office_id', $submitter->branch_office_id)
                ->whereKeyNot($submitter->id)
                ->permission('create_cash::flow::approval') // spatie scope
                ->each(function (User $user) use ($submitter, $record) {
                    $user->notify(
                        Notification::make()
                            ->title('New Cash Flow Submission')
                            ->body("A new Cash Flow has been submitted by {$submitter->name} and is pending your approval.")
                            ->actions([
                                Actions\Action::make('view_cash_flow')
                                    ->label('View Cash Flow')
                                    ->url(fn() => CashFlowResource::getUrl('view', ['record' => $record]))
                                    ->icon('heroicon-o-eye'),
                            ])
                            ->info()
                            ->toDatabase(),
                    );
                });
        } catch (\Exception $e) {
            Log::error('Error sending Cash Flow approval notifications: ' . $e->getMessage());
        }

        return $record;
    }
}
