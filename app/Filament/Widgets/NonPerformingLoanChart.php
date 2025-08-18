<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class NonPerformingLoanChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Non Performing Loan (NPL)';
    protected static ?int $sort = -7;

    protected function getData(): array
    {
        $kolek = Cache::get('dashboard:kolek', []);
        $npl = array_filter($kolek, fn($item) => !in_array($item->kolek, ['L', 'DP']));
        $nplPerKC = collect($npl)
            ->groupBy('kantor')
            ->map(fn($items) => count($items) / count($kolek) * 100);
        $kc = BranchOffice::all()
            ->mapWithKeys(function (BranchOffice $branchOffice) {
                return [
                    $branchOffice->branch_code => $branchOffice->branch_name,
                ];
            });
        $labels = $nplPerKC->keys()->map(function ($key) use ($kc) {
            return $kc->get($key, $key);
        });

        return [
            'labels' => $labels->toArray(),
            'datasets' => [
                [
                    'label' => 'NPL',
                    'data' => $nplPerKC->values()->toArray(),
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
        ];
    }
}
