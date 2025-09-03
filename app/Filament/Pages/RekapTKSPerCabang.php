<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CashRatioChart;
use App\Filament\Widgets\LoanToDepositRatioChart;
use App\Filament\Widgets\NonPerformingLoanChart;
use Filament\Pages\Page;

class RekapTKSPerCabang extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'Rekap TKS Per Cabang';
    protected static string $view = 'filament.pages.rekap-t-k-s-per-cabang';

    protected function getHeaderWidgets(): array
    {
        return [
            CashRatioChart::class,
            LoanToDepositRatioChart::class,
            NonPerformingLoanChart::class,
        ];
    }
}
