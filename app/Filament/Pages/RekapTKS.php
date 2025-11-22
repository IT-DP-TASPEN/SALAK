<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TKSRatioStatsOverview;
use App\Filament\Widgets\TKSDateFilter;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class RekapTKS extends Page
{
    use HasPageShield;

    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'Rekap TKS';
    protected static string $view = 'filament.pages.rekap-t-k-s';

    public function getHeading(): string | Htmlable
    {
        $user = auth()->user();
        if ($user->isKantorPusatEmployee()) {
            return 'Rekap TKS';
        }
        return 'Rekap TKS - ' . $user->branchOffice->branch_name;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TKSDateFilter::class,
            TKSRatioStatsOverview::class,
        ];
    }
}
