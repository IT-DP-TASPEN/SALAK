<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class CashRatioChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Cash Ratio';
    protected static ?int $sort = -9;

    protected function getData(): array
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $cashRatioPerKC = BranchOffice::all()
            ->mapWithKeys(function (BranchOffice $branchOffice) use ($yesterday) {
                return [
                    $branchOffice->branch_name => $branchOffice->cashRatio($yesterday),
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
