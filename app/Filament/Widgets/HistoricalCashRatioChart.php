<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class HistoricalCashRatioChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Cash Ratio (Historical)';
    protected static ?int $sort = -8;

    protected function getData(): array
    {
        $cashRatioHistory = Cache::get('dashboard:cash-ratio-history', collect());
        $labels = $cashRatioHistory->keys()->map(function ($date) {
            return Carbon::parse($date)->format('d M Y');
        });
        $data = $cashRatioHistory->values()->map(function ($ratio) {
            return round($ratio, 2);
        });

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cash Ratio (Historical)',
                    'data' => $data,
                    'backgroundColor' => '#4CAF50',
                    'borderColor' => '#4CAF50',
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
