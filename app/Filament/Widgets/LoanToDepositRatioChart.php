<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Filament\Widgets\ChartWidget;

class LoanToDepositRatioChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'LDR';
    protected static ?int $sort = -8;

    protected function getData(): array
    {
        $ldrPerKC = BranchOffice::all()
            ->mapWithKeys(function (BranchOffice $branchOffice) {
                return [
                    $branchOffice->branch_name => $branchOffice->loanToDepositRatio(),
                ];
            });
        $labels = $ldrPerKC->keys()->toArray();
        $data = $ldrPerKC->values()->toArray();
        $data = array_map(fn($value) => number_format($value, 2, ',', '.') . '%', $data);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Loan to Deposit Ratio',
                    'data' => $data,
                    'backgroundColor' => '#FF9800',
                    'borderColor' => '#FF9800',
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
