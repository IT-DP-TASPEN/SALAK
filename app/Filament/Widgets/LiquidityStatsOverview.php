<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class LiquidityStatsOverview extends BaseWidget
{
    protected static ?int $sort = -10;
    public ?string $tanggal = null;
    public bool $simulated = false;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $kolek = Cache::get('dashboard:kolek', []);
        $npl = array_filter($kolek, fn($item) => !in_array($item->kolek, ['L', 'DP']));
        // Calculate NPL percentage based on kre_baki_debet
        $totalBakiDebet = array_sum(array_column($kolek, 'baki_debet'));
        $totalNplBakiDebet = array_sum(array_column($npl, 'baki_debet'));
        $nplPercentage = $totalBakiDebet > 0
            ? ($totalNplBakiDebet / $totalBakiDebet) * 100
            : 0;
        $cashRatio = BranchOffice::konsolidasiCashRatio($this->tanggal, $this->simulated);
        $ldr = BranchOffice::konsolidasiLDR($this->tanggal, $this->simulated);

        $kshtNpl = match (true) {
            $nplPercentage <= 5 => [
                'level' => 'Tidak signifikan',
                'color' => 'success',
            ],
            $nplPercentage > 5 && $nplPercentage <= 6 => [
                'level' => 'Kurang signifikan',
                'color' => 'warning',
            ],
            $nplPercentage > 6 && $nplPercentage <= 7 => [
                'level' => 'Cukup signifikan',
                'color' => 'danger',
            ],
            default => [
                'level' => 'Sangat signifikan',
                'color' => 'danger',
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

        $kshtLdr = match (true) {
            $ldr <= 90 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $ldr > 90 && $nplPercentage <= 5 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $ldr > 90 && $nplPercentage > 5 && $nplPercentage <= 6 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $ldr > 90 && $nplPercentage > 6 && $nplPercentage <= 7 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            $ldr > 90 && $nplPercentage > 7 => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return [
            Stat::make('Cash Ratio', number_format($cashRatio, 2, ',', '.') . '%')
                ->color($kshtCashRatio['color'])
                ->description($kshtCashRatio['description'])
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('NPL', number_format($nplPercentage, 2, ',', '.') . '%')
                ->color($kshtNpl['color'])
                ->description('NPL ' . $kshtNpl['level'])
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('LDR', number_format($ldr, 2, ',', '.') . '%')
                ->color($kshtLdr['color'])
                ->description($kshtLdr['description'])
                ->icon('heroicon-o-chart-bar'),
        ];
    }

    #[On('chartTypeChanged')]
    public function handleChartTypeChanged(string $chartType): void
    {
        match ($chartType) {
            'realtime' => $this->tanggal = null,
            'yesterday' => $this->tanggal = Carbon::yesterday()->format('Y-m-d'),
            'simulation' => $this->tanggal = Carbon::now()->format('Y-m-d'),
        };

        $this->simulated = $chartType === 'simulation';
    }
}
