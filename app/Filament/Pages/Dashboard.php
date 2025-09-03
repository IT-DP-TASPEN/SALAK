<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountWidget;
use App\Filament\Widgets\CashRatioChart;
use App\Filament\Widgets\ChartTypeFilter;
use App\Filament\Widgets\HistoricalCashRatioChart;
use App\Filament\Widgets\HistoricalLoanToDepositRatioChart;
use App\Filament\Widgets\LiquidityStatsOverview;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            AccountWidget::class,
            ChartTypeFilter::class,
            LiquidityStatsOverview::class,
            HistoricalCashRatioChart::class,
            HistoricalLoanToDepositRatioChart::class,
        ];
    }
}
