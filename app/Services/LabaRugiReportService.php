<?php

namespace App\Services;

use Carbon\Carbon;

class LabaRugiReportService
{
    /**
     * @return array<string, array{label: string, rows: array<int, array<string, mixed>>}>
     */
    public function build(?string $date = null, ?string $branchCode = null): array
    {
        $sections = config('laba-rugi.sections', []);
        $asOf = $this->normalizeDate($date);
        $branchCode = $this->normalizeBranchCode($branchCode);
        $balanceService = app(ReportBalanceService::class);
        $balances = $balanceService->loadCurrentCoaBalances($sections, $asOf, $branchCode);
        $previousBalances = $balanceService->loadPreviousRowBalances($sections, $asOf, $branchCode);
        $trees = [];

        foreach ($sections as $sectionKey => $sectionConfig) {
            $trees[$sectionKey] = $this->buildTree($sectionConfig['rows'] ?? []);
        }

        $valueResolver = fn (array $row, string $pos): float => $balanceService->sumCoaBalances($row['coas'] ?? [], $balances);
        $previousValueResolver = fn (array $row, string $pos): float => (float) ($previousBalances[$pos] ?? 0.0);
        $groupTotals = $this->buildGroupTotals($trees, $valueResolver);
        $previousGroupTotals = $this->buildGroupTotals($trees, $previousValueResolver);
        $formulaValues = $this->buildFormulaValues($groupTotals);
        $previousFormulaValues = $this->buildFormulaValues($previousGroupTotals);

        $report = [];

        foreach ($sections as $sectionKey => $sectionConfig) {
            $report[$sectionKey] = [
                'label' => (string) ($sectionConfig['label'] ?? $sectionKey),
                'rows' => $this->buildReportRows(
                    $trees[$sectionKey] ?? [],
                    $valueResolver,
                    $previousValueResolver,
                    $formulaValues,
                    $previousFormulaValues,
                    $balanceService,
                ),
            ];
        }

        return $report;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(array $rows): array
    {
        $index = 0;

        return $this->buildTreeLevel($rows, $index);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildTreeLevel(array $rows, int &$index, ?int $parentIndent = null): array
    {
        $tree = [];

        while (isset($rows[$index])) {
            $description = (string) ($rows[$index]['description'] ?? '');
            $indent = strlen($description) - strlen(ltrim($description, ' '));

            if ($parentIndent !== null && $indent <= $parentIndent) {
                break;
            }

            $node = $rows[$index];
            $node['description'] = ltrim($description, ' ');
            $node['children'] = [];
            $index++;

            if (isset($rows[$index])) {
                $nextDescription = (string) ($rows[$index]['description'] ?? '');
                $nextIndent = strlen($nextDescription) - strlen(ltrim($nextDescription, ' '));

                if ($nextIndent > $indent) {
                    $node['children'] = $this->buildTreeLevel($rows, $index, $indent);
                }
            }

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $trees
     * @param  callable(array<string, mixed>, string): float  $valueResolver
     * @return array<string, float>
     */
    private function buildGroupTotals(array $trees, callable $valueResolver): array
    {
        $groupTotals = [];

        foreach ($trees as $rows) {
            $this->addLeafGroupTotals($rows, $valueResolver, $groupTotals);
        }

        return $groupTotals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>, string): float  $valueResolver
     * @param  array<string, float>  $groupTotals
     */
    private function addLeafGroupTotals(array $rows, callable $valueResolver, array &$groupTotals): void
    {
        foreach ($rows as $row) {
            if (($row['children'] ?? []) !== []) {
                $this->addLeafGroupTotals($row['children'], $valueResolver, $groupTotals);

                continue;
            }

            if (filled($row['formula'] ?? null)) {
                continue;
            }

            $group = (string) ($row['group'] ?? '');

            if ($group !== '') {
                $pos = (string) ($row['pos'] ?? '');
                $groupTotals[$group] = ($groupTotals[$group] ?? 0.0) + $valueResolver($row, $pos);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>, string): float  $valueResolver
     * @param  callable(array<string, mixed>, string): float  $previousValueResolver
     * @param  array<string, float>  $formulaValues
     * @param  array<string, float>  $previousFormulaValues
     * @return array<int, array<string, mixed>>
     */
    private function buildReportRows(
        array $rows,
        callable $valueResolver,
        callable $previousValueResolver,
        array $formulaValues,
        array $previousFormulaValues,
        ReportBalanceService $balanceService,
    ): array {
        $reportRows = [];

        foreach ($rows as $row) {
            $children = $this->buildReportRows(
                $row['children'] ?? [],
                $valueResolver,
                $previousValueResolver,
                $formulaValues,
                $previousFormulaValues,
                $balanceService,
            );
            $formula = (string) ($row['formula'] ?? '');
            $pos = (string) ($row['pos'] ?? '');

            if ($formula !== '') {
                $value = (float) ($formulaValues[$formula] ?? 0.0);
                $previousValue = (float) ($previousFormulaValues[$formula] ?? 0.0);
            } elseif ($children !== []) {
                $value = (float) array_sum(array_column($children, 'value'));
                $previousValue = (float) array_sum(array_column($children, 'previous_value'));
            } else {
                $value = $valueResolver($row, $pos);
                $previousValue = $previousValueResolver($row, $pos);
            }

            $reportRows[] = [
                'pos' => $pos,
                'description' => (string) ($row['description'] ?? ''),
                'value' => $value,
                'previous_value' => $previousValue,
                'yoy_percent' => $balanceService->yoyPercent($value, $previousValue),
                'is_total' => (bool) ($row['is_total'] ?? false),
                'children' => $children,
            ];
        }

        return $reportRows;
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
