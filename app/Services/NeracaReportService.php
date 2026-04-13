<?php

namespace App\Services;

use App\Models\BranchOffice;
use Carbon\Carbon;

class NeracaReportService
{
    /**
     * @return array<string, array{label: string, rows: array<int, array{pos: string, description: string, value: float, is_total: bool}>}>
     */
    public function build(?string $date = null, ?string $branchCode = null): array
    {
        $sections = config('neraca.sections', []);
        $asOf = $this->normalizeDate($date);
        $branchCode = $this->normalizeBranchCode($branchCode);
        $balances = $this->loadBalances($sections, $asOf, $branchCode);

        $report = [];

        foreach ($sections as $sectionKey => $sectionConfig) {
            $rows = [];
            $runningTotal = 0.0;

            foreach ($sectionConfig['rows'] ?? [] as $rowConfig) {
                $isTotal = (bool) ($rowConfig['is_total'] ?? false);

                $value = $isTotal
                    ? $runningTotal
                    : $this->sumBalances($rowConfig['coas'] ?? [], $balances);

                if (! $isTotal) {
                    $runningTotal += $value;
                }

                $rows[] = [
                    'pos' => (string) ($rowConfig['pos'] ?? ''),
                    'description' => (string) ($rowConfig['description'] ?? ''),
                    'value' => $value,
                    'is_total' => $isTotal,
                ];
            }

            $report[$sectionKey] = [
                'label' => (string) ($sectionConfig['label'] ?? $sectionKey),
                'rows' => $rows,
            ];
        }

        return $report;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, float|int>
     */
    private function loadBalances(array $sections, string $date, ?string $branchCode): array
    {
        $coas = [];

        foreach ($sections as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                foreach ($row['coas'] ?? [] as $coa) {
                    $coa = trim((string) $coa);

                    if ($coa === '') {
                        continue;
                    }

                    $coas[$coa] = $coa;
                }
            }
        }

        if ($coas === []) {
            return [];
        }

        return BranchOffice::saldoNeraca2(array_values($coas), $branchCode, $date);
    }

    /**
     * @param  array<int, string>  $coas
     * @param  array<string, float|int>  $balances
     */
    private function sumBalances(array $coas, array $balances): float
    {
        $total = 0.0;

        foreach ($coas as $coa) {
            $total += (float) ($balances[(string) $coa] ?? 0.0);
        }

        return $total;
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
}
