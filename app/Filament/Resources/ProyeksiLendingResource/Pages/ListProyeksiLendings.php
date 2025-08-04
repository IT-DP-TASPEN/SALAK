<?php

namespace App\Filament\Resources\ProyeksiLendingResource\Pages;

use App\Filament\Resources\ProyeksiLendingResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProyeksiLendings extends ListRecords
{
    protected static string $resource = ProyeksiLendingResource::class;

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
            ->modifyQueryUsing(fn(Builder $query) => $query->whereHas(
                'approvals',
                fn(Builder $query) =>
                $query->where('approval_status', 'pending')
            ))
            ->badge(
                fn() => $this->getModel()::whereHas(
                    'approvals',
                    fn(Builder $query) =>
                    $query->where('approval_status', 'pending')
                )->count()
            );

        $tabs['approved'] = Tab::make()
            ->label('Approved')
            ->icon('heroicon-o-check-circle')
            ->modifyQueryUsing(fn(Builder $query) => $query->whereHas(
                'approvals',
                fn(Builder $query) =>
                $query->where('approval_status', 'approved')
            ))
            ->badge(
                fn() => $this->getModel()::whereHas(
                    'approvals',
                    fn(Builder $query) =>
                    $query->where('approval_status', 'approved')
                )->count()
            );

        $tabs['rejected'] = Tab::make()
            ->label('Rejected')
            ->icon('heroicon-o-x-circle')
            ->modifyQueryUsing(fn(Builder $query) => $query->whereHas(
                'approvals',
                fn(Builder $query) =>
                $query->where('approval_status', 'rejected')
            ))
            ->badge(
                fn() => $this->getModel()::whereHas(
                    'approvals',
                    fn(Builder $query) =>
                    $query->where('approval_status', 'rejected')
                )->count()
            );

        return $tabs;
    }
}
