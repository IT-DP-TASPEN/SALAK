<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Filament\Widgets\ChartWidget;

class CashRatioChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Cash Ratio';
    protected static ?int $sort = -9;

    protected function getData(): array
    {
        $cashRatioPerKC = BranchOffice::all()
            ->mapWithKeys(function (BranchOffice $branchOffice) {
                return [
                    $branchOffice->branch_name => $branchOffice->cashRatio(),
                ];
            });
        $labels = $cashRatioPerKC->keys()->toArray();
        $data = $cashRatioPerKC->values()->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cash Ratio',
                    'data' => $data,
                    'backgroundColor' => '#4CAF50',
                    'borderColor' => '#4CAF50',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
        ];
    }
}
