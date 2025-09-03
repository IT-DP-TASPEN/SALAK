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
        $totalBakiDebet = array_sum(array_column($kolek, 'baki_debet'));
        $npl = array_filter($kolek, fn($item) => !in_array($item->kolek, ['L', 'DP']));
        $nplPerKC = collect($npl)->groupBy('kantor')->map(function ($items) use ($totalBakiDebet) {
            $totalNplBakiDebet = array_sum(array_column($items->toArray(), 'baki_debet'));
            return $totalBakiDebet > 0 ? ($totalNplBakiDebet / $totalBakiDebet) * 100 : 0;
        });
        $labels = collect($nplPerKC->keys())->map(function ($kantor) {
            return BranchOffice::where('branch_code', $kantor)->first()->branch_name ?? $kantor;
        });
        $nplPerKC = $nplPerKC->map(function ($value) {
            return round($value, 2);
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
            'scales' => [
                'x' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 5,
                ],
            ]
        ];
    }
}
