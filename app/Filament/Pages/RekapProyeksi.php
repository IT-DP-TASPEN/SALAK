<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\ProyeksiFundingStatsOverview;
use App\Filament\Widgets\ProyeksiLendingStatsOverview;

class RekapProyeksi extends Page
{
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.rekap-proyeksi';

    protected function getHeaderWidgets(): array
    {
        return [
            ProyeksiFundingStatsOverview::class,
            ProyeksiLendingStatsOverview::class,
        ];
    }
}
