<?php

namespace App\Filament\Resources\CashFlowResource\Pages;

use App\Filament\Resources\CashFlowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCashFlows extends ListRecords
{
    protected static string $resource = CashFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()->label('All')
                ->icon('heroicon-o-document-text')
        ];

        $tabs['needs-approval'] = Tab::make()
            ->label('Needs Approval')
            ->icon('heroicon-o-clock')
            ->modifyQueryUsing(fn(Builder $query) => CashFlowResource::getEloquentQuery()
                ->whereHas(
                    'approval',
                    fn(Builder $query) =>
                    $query->where('approval_status', 'pending')
                ))
            ->badge(
                fn() => CashFlowResource::getEloquentQuery()
                    ->whereHas(
                        'approval',
                        fn(Builder $query) =>
                        $query->where('approval_status', 'pending')
                    )->count()
            );

        $tabs['approved'] = Tab::make()
            ->label('Approved')
            ->icon('heroicon-o-check-circle')
            ->modifyQueryUsing(fn(Builder $query) => CashFlowResource::getEloquentQuery()
                ->whereHas(
                    'approval',
                    fn(Builder $query) =>
                    $query->where('approval_status', 'approved')
                ))
            ->badge(
                fn() => CashFlowResource::getEloquentQuery()
                    ->whereHas(
                        'approval',
                        fn(Builder $query) =>
                        $query->where('approval_status', 'approved')
                    )->count()
            );

        $tabs['rejected'] = Tab::make()
            ->label('Rejected')
            ->icon('heroicon-o-x-circle')
            ->modifyQueryUsing(fn(Builder $query) => CashFlowResource::getEloquentQuery()
                ->whereHas(
                    'approval',
                    fn(Builder $query) =>
                    $query->where('approval_status', 'rejected')
                ))
            ->badge(
                fn() => CashFlowResource::getEloquentQuery()
                    ->whereHas(
                        'approval',
                        fn(Builder $query) =>
                        $query->where('approval_status', 'rejected')
                    )->count()
            );

        return $tabs;
    }
}
