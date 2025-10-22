<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Filament\Widgets\ChartWidget;

class NonPerformingLoanChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Non Performing Loan (NPL)';
    protected static ?int $sort = -7;

    protected function getData(): array
    {
        $nplPerKC = collect(BranchOffice::all())->mapWithKeys(function (BranchOffice $branch) {
            $npl = $branch->npl();
            return [$branch->branch_name => round($npl, 2)];
        });
        $labels = $nplPerKC->keys()->toArray();
        $values = $nplPerKC->values()->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'NPL',
                    'data' => $values,
                    'backgroundColor' => '#F44336',
                    'borderColor' => '#F44336',
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
            'scales' => [
                'x' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 5,
                ],
            ]
        ];
    }
}
