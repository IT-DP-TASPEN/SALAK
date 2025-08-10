<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use App\Models\BranchOffice;
use App\Models\ProyeksiFunding;

class ProyeksiFundingStatsOverview extends BaseWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Proyeksi Funding';

    protected function getStats(): array
    {
        $today = Carbon::today();
        $branches = BranchOffice::orderBy('branch_code')->get();
        $fundingsToday = ProyeksiFunding::whereDate('funding_tanggal', $today)
            ->selectRaw('funding_kantor, SUM(funding_nominal) as total_funding')
            ->groupBy('funding_kantor')
            ->pluck('total_funding', 'funding_kantor');

        $stats = $branches->map(function ($branch) use ($fundingsToday) {
            $total = $fundingsToday[$branch->id] ?? 0;

            return Stat::make(
                $branch->branch_name,
                'Rp. ' . number_format($total, 0, ',', '.')
            )
                ->description('Total Funding Hari Ini')
                ->color('primary')
                ->icon('heroicon-o-building-office-2');
        });

        return $stats->all();
    }
}
