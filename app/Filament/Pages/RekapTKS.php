<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TKSRatioStatsOverview;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class RekapTKS extends Page
{
    use HasPageShield;

    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'Rekap TKS';
    protected static string $view = 'filament.pages.rekap-t-k-s';

    protected function getHeaderWidgets(): array
    {
        return [
            TKSRatioStatsOverview::class,
        ];
    }
}
