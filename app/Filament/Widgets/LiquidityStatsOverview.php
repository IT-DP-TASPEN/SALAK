<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class LiquidityStatsOverview extends BaseWidget
{
    protected static ?int $sort = -10;
    public ?string $tanggal = null;
    public bool $simulated = false;
    public bool $efektif = true;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $nplPercentage = BranchOffice::konsolidasiNPL();
        $cashRatio = BranchOffice::konsolidasiCashRatio($this->tanggal, $this->simulated, $this->efektif);
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

    #[On('balanceTypeChanged')]
    public function handleBalanceTypeChanged(string $balanceType): void
    {
        $this->efektif = $balanceType === 'effective';
    }
}
