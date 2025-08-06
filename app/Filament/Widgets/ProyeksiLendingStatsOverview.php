<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use App\Models\ProyeksiLending;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProyeksiLendingStatsOverview extends BaseWidget
{
    protected ?string $heading = 'Proyeksi Lending';

    protected function getStats(): array
    {
        $today = Carbon::today();
        $branches = BranchOffice::orderBy('branch_name')->get();
        $lendingsToday = ProyeksiLending::whereDate('lending_tanggal', $today)
            ->selectRaw('lending_kantor, SUM(lending_plafond) as total_booking')
            ->groupBy('lending_kantor')
            ->pluck('total_booking', 'lending_kantor');

        $stats = $branches->map(function ($branch) use ($lendingsToday) {
            $total = $lendingsToday[$branch->id] ?? 0;

            return Stat::make(
                $branch->branch_name,
                'Rp. ' . number_format($total, 0, ',', '.')
            )
                ->description('Total Booking Hari Ini')
                ->color('primary')
                ->icon('heroicon-o-building-office-2');
        });

        return $stats->all();
    }
}
