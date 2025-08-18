<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class LiquidityStatsOverview extends BaseWidget
{
    protected static ?int $sort = -10;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $kolek = Cache::get('dashboard:kolek', []);
        $npl = array_filter($kolek, fn($item) => !in_array($item->kolek, ['L', 'DP']));
        $nplPercentage = (count($npl) / count($kolek)) * 100;
        $cashRatio = BranchOffice::konsolidasiCashRatio();
        $ldr = BranchOffice::konsolidasiLDR();

        $kshtNpl = match (true) {
            $nplPercentage >= 5 => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
            $nplPercentage >= 3 && $nplPercentage < 5 => [
                'color' => 'success',
                'description' => 'Cukup sehat',
            ],
            default => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
        };

        $kshtCashRatio = match (true) {
            $cashRatio >= 4.05 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $cashRatio >= 3.30 && $cashRatio < 4.05 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $cashRatio >= 2.55 && $cashRatio < 3.30 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $cashRatio >= 1.8 && $cashRatio < 2.55 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        $kshtLDR = match (true) {
            $ldr > 100 && $nplPercentage > 5 => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
            $ldr > 100 && $nplPercentage <= 5 => [
                'color' => 'success',
                'description' => 'Cukup sehat',
            ],
            $ldr > 100 => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
            $ldr >= 98.25 && $ldr <= 100 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            $ldr >= 96.5 && $ldr < 98.25 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $ldr >= 94.75 && $ldr < 96.5 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            default => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
        };

        return [
            Stat::make('Cash Ratio', number_format($cashRatio, 2, ',', '.') . '%')
                ->color($kshtCashRatio['color'])
                ->description($kshtCashRatio['description'])
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('NPL', number_format($nplPercentage, 2, ',', '.') . '%')
                ->color($kshtNpl['color'])
                ->description($kshtNpl['description'])
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('LDR', number_format($ldr, 2, ',', '.') . '%')
                ->color($kshtLDR['color'])
                ->description($kshtLDR['description'])
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
