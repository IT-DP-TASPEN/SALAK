<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TKSRatioStatsOverview;
use Filament\Pages\Page;

class RekapTKS extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.rekap-t-k-s';

    protected function getHeaderWidgets(): array
    {
        return [
            TKSRatioStatsOverview::class,
        ];
    }
}
