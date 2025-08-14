<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LiquidityStatsOverview extends BaseWidget
{
    protected static ?int $sort = -10;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $cashRatio = number_format(BranchOffice::konsolidasiCashRatio(), 2, ',', '.') . '%';
        $ldr = number_format(BranchOffice::konsolidasiLDR(), 2, ',', '.') . '%';

        return [
            Stat::make('Cash Ratio', $cashRatio)
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('LDR', $ldr)
                ->color('warning')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
