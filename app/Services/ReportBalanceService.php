<?php

namespace App\Services;

use App\Models\BranchOffice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportBalanceService
{
    private const MSO_CUTOFF_DATE = '2025-10-13';

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, float|int>
     */
    public function loadCurrentCoaBalances(array $sections, string $date, ?string $branchCode): array
    {
        return $this->loadCoaBalances($this->collectCoas($sections), $date, $branchCode);
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, float>
     */
    public function loadPreviousRowBalances(array $sections, string $date, ?string $branchCode): array
    {
        $previousDate = $this->previousDate($date);

        if (Carbon::parse($previousDate)->lt(Carbon::parse(self::MSO_CUTOFF_DATE))) {
            return $this->loadMsoRowBalances($sections, $previousDate, $branchCode);
        }

        $balances = $this->loadCoaBalances($this->collectCoas($sections), $previousDate, $branchCode);
        $rowBalances = [];

        foreach ($this->detailRows($sections) as $row) {
            $pos = (string) ($row['pos'] ?? '');
            $rowBalances[$pos] = $this->sumCoaBalances($row['coas'] ?? [], $balances);
        }

        return $rowBalances;
    }

    public function yoyPercent(float $value, float $previousValue): ?float
    {
        if (abs($previousValue) < 0.00001) {
            return null;
        }

        return (($value - $previousValue) / abs($previousValue)) * 100;
    }

    /**
     * @param  array<int, string>  $coas
     * @param  array<string, float|int>  $balances
     */
    public function sumCoaBalances(array $coas, array $balances): float
    {
        $total = 0.0;

        foreach ($coas as $coa) {
            $total += (float) ($balances[(string) $coa] ?? 0.0);
        }

        return $total;
    }

    public function previousDate(string $date): string
    {
        return Carbon::parse($date)->subYearNoOverflow()->toDateString();
    }

    /**
     * @param  array<int, string>  $coas
     * @return array<string, float|int>
     */
    private function loadCoaBalances(array $coas, string $date, ?string $branchCode): array
    {
        if ($coas === []) {
            return [];
        }

        return BranchOffice::saldoNeraca2($coas, $branchCode, $date);
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, float>
     */
    private function loadMsoRowBalances(array $sections, string $date, ?string $branchCode): array
    {
        $positions = [];

        foreach ($this->detailRows($sections) as $row) {
            $pos = trim((string) ($row['pos'] ?? ''));

            if ($pos !== '') {
                $positions[$pos] = $pos;
            }
        }

        if ($positions === []) {
            return [];
        }

        $result = array_fill_keys(array_values($positions), 0.0);
        $msoBranchCode = $this->normalizeMsoBranchCode($branchCode);

        $rows = DB::connection('mso-backup')
            ->table('kode_perksandi')
            ->select('sandip_pos')
            ->selectRaw(
                'SUM(COALESCE(GetPerkSaldo(sandip_mbs, ?, ?), 0) * COALESCE(sandip_notasi, 0)) as saldo',
                [$msoBranchCode, $date],
            )
            ->whereIn('sandip_pos', array_values($positions))
            ->whereRaw("TRIM(COALESCE(sandip_mbs, '')) <> ''")
            ->where('sandip_notasi', '!=', 0)
            ->groupBy('sandip_pos')
            ->pluck('saldo', 'sandip_pos');

        foreach ($rows as $pos => $saldo) {
            $result[(string) $pos] = (float) $saldo;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function detailRows(array $sections): array
    {
        $rows = [];

        foreach ($sections as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                if ((bool) ($row['is_total'] ?? false)) {
                    continue;
                }

                if (filled($row['formula'] ?? null)) {
                    continue;
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<int, string>
     */
    private function collectCoas(array $sections): array
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

        return array_values($coas);
    }

    private function normalizeMsoBranchCode(?string $branchCode): ?string
    {
        $branchCode = is_string($branchCode) ? trim($branchCode) : $branchCode;

        if ($branchCode !== null && strlen($branchCode) === 3) {
            return substr($branchCode, -2);
        }

        return blank($branchCode) ? null : $branchCode;
    }
}
