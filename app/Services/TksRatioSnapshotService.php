<?php

namespace App\Services;

use App\Models\BranchOffice;
use App\Models\LoanOutstanding;
use App\Models\SaldoNeraca;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class TksRatioSnapshotService
{
    /**
     * @return array<string, float>
     */
    public function getSnapshot(?string $date = null, ?string $branch = null): array
    {
        $asOf = $this->normalizeDate($date);
        $branch = $this->normalizeBranch($branch);

        if ($this->shouldBypassCache($asOf, $branch)) {
            return $this->buildSnapshot($asOf->toDateString(), $branch);
        }

        $cacheKey = $this->cacheKey($asOf->toDateString(), $branch);

        if (Cache::has($cacheKey)) {
            /** @var array<string, float> $cached */
            $cached = Cache::get($cacheKey, []);

            return $cached;
        }

        try {
            /** @var array<string, float> */
            return Cache::lock($this->lockKey($asOf->toDateString(), $branch), 300)
                ->block(10, function () use ($asOf, $branch, $cacheKey): array {
                    /** @var array<string, float> */
                    return Cache::rememberForever(
                        $cacheKey,
                        fn(): array => $this->buildSnapshot($asOf->toDateString(), $branch),
                    );
                });
        } catch (LockTimeoutException) {
            /** @var array<string, float>|null $cached */
            $cached = Cache::get($cacheKey);

            return $cached ?? $this->buildSnapshot($asOf->toDateString(), $branch);
        }
    }

    /**
     * @return array<string, float>|null
     */
    public function warmSnapshot(string $date, ?string $branch = null, bool $refresh = false): ?array
    {
        $asOf = $this->normalizeDate($date);
        $branch = $this->normalizeBranch($branch);

        if ($asOf->isToday() || ! $this->hasCoreSourceData($asOf->toDateString(), $branch)) {
            return null;
        }

        $cacheKey = $this->cacheKey($asOf->toDateString(), $branch);

        if (! $refresh && Cache::has($cacheKey)) {
            /** @var array<string, float> $cached */
            $cached = Cache::get($cacheKey, []);

            return $cached;
        }

        try {
            /** @var array<string, float> */
            return Cache::lock($this->lockKey($asOf->toDateString(), $branch), 300)
                ->block(10, function () use ($asOf, $branch, $refresh, $cacheKey): array {
                    if (! $refresh && Cache::has($cacheKey)) {
                        /** @var array<string, float> $cached */
                        $cached = Cache::get($cacheKey, []);

                        return $cached;
                    }

                    $snapshot = $this->buildSnapshot($asOf->toDateString(), $branch);
                    Cache::forever($cacheKey, $snapshot);

                    return $snapshot;
                });
        } catch (LockTimeoutException) {
            /** @var array<string, float>|null $cached */
            $cached = Cache::get($cacheKey);

            return $cached;
        }
    }

    public function hasCoreSourceData(string $date, ?string $branch = null): bool
    {
        $branch = $this->normalizeBranch($branch);

        return SaldoNeraca::query()
            ->where('tanggal', $date)
            ->when($branch, fn($query) => $query->where('cabang', $branch))
            ->exists()
            && LoanOutstanding::query()
                ->where('loan_date_params', $date)
                ->when($branch, fn($query) => $query->where('loan_branch_office', $branch))
                ->exists();
    }

    public function hasCachedSnapshot(string $date, ?string $branch = null): bool
    {
        return Cache::has($this->cacheKey($date, $this->normalizeBranch($branch)));
    }

    public function cacheKey(string $date, ?string $branch = null): string
    {
        return sprintf('rekap-tks:ratios:%s:%s', $date, $branch ?: 'all');
    }

    private function lockKey(string $date, ?string $branch = null): string
    {
        return $this->cacheKey($date, $branch) . ':lock';
    }

    private function shouldBypassCache(Carbon $asOf, ?string $branch = null): bool
    {
        return $asOf->isToday() || ! $this->hasCoreSourceData($asOf->toDateString(), $branch);
    }

    private function normalizeDate(?string $date = null): Carbon
    {
        $asOf = $date ? Carbon::parse($date)->startOfDay() : Carbon::today();

        if ($asOf->isFuture()) {
            return Carbon::today();
        }

        return $asOf;
    }

    private function normalizeBranch(?string $branch = null): ?string
    {
        $branch = is_string($branch) ? trim($branch) : $branch;

        return blank($branch) ? null : $branch;
    }

    /**
     * @return array<string, float>
     */
    private function buildSnapshot(string $date, ?string $branch = null): array
    {
        return [
            'kpmm' => BranchOffice::konsolidasiKPMM($date, $branch),
            'ckpn_per_ppka' => BranchOffice::konsolidasiCKPNPerPPKA($date, $branch),
            'npl_nett' => BranchOffice::konsolidasiNPLNett($date, $branch),
            'npl' => BranchOffice::konsolidasiNPL($date, $branch),
            'roa' => BranchOffice::konsolidasiROA($date, $branch),
            'bopo' => BranchOffice::konsolidasiBopo($date, $branch),
            'nim' => BranchOffice::konsolidasiNim($date, $branch),
            'ldr' => BranchOffice::konsolidasiLDR($date, false, $branch),
            'cash_ratio' => BranchOffice::konsolidasiCashRatio($date, false, true, $branch),
            'miapb' => BranchOffice::konsolidasiMIAPB($date, $branch),
        ];
    }
}
