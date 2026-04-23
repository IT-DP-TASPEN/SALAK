<?php

namespace App\Services;

use App\Models\BranchOffice;
use Carbon\Carbon;

class LabaRugiReportService
{
    /**
     * @return array<string, array{label: string, rows: array<int, array{pos: string, description: string, value: float, is_total: bool}>}>
     */
    public function build(?string $date = null, ?string $branchCode = null): array
    {
        $sections = config('laba-rugi.sections', []);
        $asOf = $this->normalizeDate($date);
        $branchCode = $this->normalizeBranchCode($branchCode);
        $balances = $this->loadBalances($sections, $asOf, $branchCode);

        [$rowValues, $groupTotals] = $this->buildDetailValues($sections, $balances);
        $formulaValues = $this->buildFormulaValues($groupTotals);

        $report = [];

        foreach ($sections as $sectionKey => $sectionConfig) {
            $rows = [];

            foreach ($sectionConfig['rows'] ?? [] as $rowConfig) {
                $formula = (string) ($rowConfig['formula'] ?? '');
                $pos = (string) ($rowConfig['pos'] ?? '');

                $rows[] = [
                    'pos' => $pos,
                    'description' => (string) ($rowConfig['description'] ?? ''),
                    'value' => $formula !== ''
                        ? (float) ($formulaValues[$formula] ?? 0.0)
                        : (float) ($rowValues[$pos] ?? 0.0),
                    'is_total' => (bool) ($rowConfig['is_total'] ?? false),
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
     * @param  array<string, float|int>  $balances
     * @return array{0: array<string, float>, 1: array<string, float>}
     */
    private function buildDetailValues(array $sections, array $balances): array
    {
        $rowValues = [];
        $groupTotals = [];

        foreach ($sections as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                if (filled($row['formula'] ?? null)) {
                    continue;
                }

                $pos = (string) ($row['pos'] ?? '');
                $group = (string) ($row['group'] ?? '');
                $value = $this->sumBalances($row['coas'] ?? [], $balances);

                $rowValues[$pos] = $value;

                if ($group !== '') {
                    $groupTotals[$group] = ($groupTotals[$group] ?? 0.0) + $value;
                }
            }
        }

        return [$rowValues, $groupTotals];
    }

    /**
     * @param  array<string, float>  $groupTotals
     * @return array<string, float>
     */
    private function buildFormulaValues(array $groupTotals): array
    {
        $operatingIncome = $this->groupTotal($groupTotals, 'operating_income');
        $operatingExpense = $this->groupTotal($groupTotals, 'operating_expense');
        $operatingProfit = $operatingIncome - $operatingExpense;

        $nonOperatingIncome = $this->groupTotal($groupTotals, 'non_operating_income');
        $nonOperatingExpense = $this->groupTotal($groupTotals, 'non_operating_expense');
        $nonOperatingProfit = $nonOperatingIncome - $nonOperatingExpense;

        $profitBeforeTax = $operatingProfit + $nonOperatingProfit;
        $currentYearProfit = $profitBeforeTax
            - $this->groupTotal($groupTotals, 'tax_expense')
            + $this->groupTotal($groupTotals, 'deferred_tax_income')
            - $this->groupTotal($groupTotals, 'deferred_tax_expense');
        $otherComprehensiveIncome = $this->groupTotal($groupTotals, 'other_comprehensive_income');

        return [
            'operating_income' => $operatingIncome,
            'operating_expense' => $operatingExpense,
            'operating_profit' => $operatingProfit,
            'non_operating_income' => $nonOperatingIncome,
            'non_operating_expense' => $nonOperatingExpense,
            'non_operating_profit' => $nonOperatingProfit,
            'profit_before_tax' => $profitBeforeTax,
            'current_year_profit' => $currentYearProfit,
            'other_comprehensive_income' => $otherComprehensiveIncome,
            'comprehensive_profit' => $currentYearProfit + $otherComprehensiveIncome,
        ];
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

    /**
     * @param  array<string, float>  $groupTotals
     */
    private function groupTotal(array $groupTotals, string $group): float
    {
        return (float) ($groupTotals[$group] ?? 0.0);
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
