<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class HistoricalLoanToDepositRatioChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Loan to Deposit Ratio (Historical)';
    protected static ?int $sort = -7;

    protected function getData(): array
    {
        $ldrHistory = Cache::get('dashboard:loan-to-deposit-ratio-history', collect());
        $labels = $ldrHistory->keys()->map(function ($date) {
            return Carbon::parse($date)->format('d M Y');
        });
        $data = $ldrHistory->values()->map(function ($ratio) {
            return round($ratio, 2);
        });
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Loan to Deposit Ratio (Historical)',
                    'data' => $data,
                    'backgroundColor' => '#2196F3',
                    'borderColor' => '#2196F3',
                    'borderWidth' => 2,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
