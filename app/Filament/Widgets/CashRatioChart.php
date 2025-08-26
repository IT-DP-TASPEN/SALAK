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

    public ?string $tanggal = null;
    public bool $simulated = false;

    protected function getData(): array
    {
        $cashRatioPerKC = BranchOffice::all()
            ->mapWithKeys(function (BranchOffice $branchOffice) {
                return [
                    $branchOffice->branch_name => $branchOffice->cashRatio($this->tanggal, $this->simulated),
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

    #[On('chartTypeChanged')]
    public function handleChartTypeChanged(string $chartType): void
    {
        match ($chartType) {
            'realtime' => $this->tanggal = null,
            'yesterday' => $this->tanggal = Carbon::yesterday()->format('Y-m-d'),
            'simulation' => $this->tanggal = Carbon::now()->format('Y-m-d'),
        };

        $this->simulated = $chartType === 'simulation';
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
