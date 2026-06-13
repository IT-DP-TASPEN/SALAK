<?php

namespace App\Services;

use Carbon\Carbon;

class NeracaReportService
{
    /**
     * @return array<string, array{label: string, rows: array<int, array{pos: string, description: string, value: float, previous_value: float, yoy_percent: ?float, is_total: bool}>}>
     */
    public function build(?string $date = null, ?string $branchCode = null, bool $showZeroBalances = true): array
    {
        $sections = config('neraca.sections', []);
        $asOf = $this->normalizeDate($date);
        $branchCode = $this->normalizeBranchCode($branchCode);
        $balanceService = app(ReportBalanceService::class);
        $balances = $balanceService->loadCurrentCoaBalances($sections, $asOf, $branchCode);
        $previousBalances = $balanceService->loadPreviousRowBalances($sections, $asOf, $branchCode);

        $report = [];

        foreach ($sections as $sectionKey => $sectionConfig) {
            $rows = [];
            $runningTotal = 0.0;
            $previousRunningTotal = 0.0;

            foreach ($sectionConfig['rows'] ?? [] as $rowConfig) {
                $isTotal = (bool) ($rowConfig['is_total'] ?? false);
                $pos = (string) ($rowConfig['pos'] ?? '');

                $value = $isTotal
                    ? $runningTotal
                    : $balanceService->sumCoaBalances($rowConfig['coas'] ?? [], $balances);

                $previousValue = $isTotal
                    ? $previousRunningTotal
                    : (float) ($previousBalances[$pos] ?? 0.0);

                if (! $isTotal) {
                    $runningTotal += $value;
                    $previousRunningTotal += $previousValue;
                }

                if (! $showZeroBalances && ! $isTotal && $this->isZero($value)) {
                    continue;
                }

                $rows[] = [
                    'pos' => $pos,
                    'description' => (string) ($rowConfig['description'] ?? ''),
                    'value' => $value,
                    'previous_value' => $previousValue,
                    'yoy_percent' => $balanceService->yoyPercent($value, $previousValue),
                    'is_total' => $isTotal,
                ];
            }

            $report[$sectionKey] = [
                'label' => (string) ($sectionConfig['label'] ?? $sectionKey),
                'rows' => $rows,
            ];
        }

        return $this->appendLiabilitiesAndEquityTotal($report, $balanceService);
    }

    /**
     * @param  array<string, array{label: string, rows: array<int, array{pos: string, description: string, value: float, previous_value: float, yoy_percent: ?float, is_total: bool}>}>  $report
     * @return array<string, array{label: string, rows: array<int, array{pos: string, description: string, value: float, previous_value: float, yoy_percent: ?float, is_total: bool}>}>
     */
    private function appendLiabilitiesAndEquityTotal(array $report, ReportBalanceService $balanceService): array
    {
        if (! isset($report['assets'])) {
            return $report;
        }

        $value = $this->sectionTotal($report['liabilities']['rows'] ?? [], 'value')
            + $this->sectionTotal($report['equity']['rows'] ?? [], 'value');
        $previousValue = $this->sectionTotal($report['liabilities']['rows'] ?? [], 'previous_value')
            + $this->sectionTotal($report['equity']['rows'] ?? [], 'previous_value');

        $report['assets']['rows'][] = [
            'pos' => '',
            'description' => 'TOTAL LIABILITAS + EKUITAS',
            'value' => $value,
            'previous_value' => $previousValue,
            'yoy_percent' => $balanceService->yoyPercent($value, $previousValue),
            'is_total' => true,
        ];

        return $report;
    }

    /**
     * @param  array<int, array{pos: string, description: string, value: float, previous_value: float, yoy_percent: ?float, is_total: bool}>  $rows
     */
    private function sectionTotal(array $rows, string $key): float
    {
        foreach (array_reverse($rows) as $row) {
            if ($row['is_total']) {
                return (float) ($row[$key] ?? 0.0);
            }
        }

        return 0.0;
    }

    private function normalizeDate(?string $date): string
    {
        $asOf = blank($date) ? Carbon::today() : Carbon::parse($date)->startOfDay();

        if ($asOf->isFuture()) {
            $asOf = Carbon::today();
        }

        return $asOf->toDateString();
    }

    private function normalizeBranchCode(?string $branchCode): ?string
    {
        $branchCode = is_string($branchCode) ? trim($branchCode) : $branchCode;

        return blank($branchCode) ? null : $branchCode;
    }

    private function isZero(float $value): bool
    {
        return abs($value) < 0.00001;
    }
}
