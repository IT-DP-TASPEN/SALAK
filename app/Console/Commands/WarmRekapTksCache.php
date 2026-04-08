<?php

namespace App\Console\Commands;

use App\Models\BranchOffice;
use App\Models\LoanOutstanding;
use App\Models\SaldoNeraca;
use App\Services\TksRatioSnapshotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class WarmRekapTksCache extends Command
{
    protected $signature = 'cache:warm-rekap-tks
        {--from= : Tanggal mulai format YYYY-MM-DD}
        {--to= : Tanggal akhir format YYYY-MM-DD}
        {--branch=* : Batasi warm cache ke satu atau lebih branch code fincloud}
        {--refresh : Hitung ulang dan overwrite cache yang sudah ada}';

    protected $description = 'Warm cache snapshot rasio Rekap TKS untuk histori sampai H-1';

    public function __construct(
        private readonly TksRatioSnapshotService $snapshotService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $availableDates = $this->availableHistoricalDates();

        if ($availableDates->isEmpty()) {
            $this->info('Tidak ada tanggal historis yang eligible untuk warm cache Rekap TKS.');

            return self::SUCCESS;
        }

        $from = $this->resolveBoundaryDate(
            (string) $this->option('from'),
            $availableDates->first(),
        );
        $to = $this->resolveBoundaryDate(
            (string) $this->option('to'),
            min($availableDates->last(), Carbon::yesterday()->toDateString()),
        );

        if ($from > $to) {
            $this->error("Rentang tanggal tidak valid: from {$from} lebih besar dari to {$to}.");

            return self::FAILURE;
        }

        $datesToWarm = $availableDates
            ->filter(fn(string $date): bool => $date >= $from && $date <= $to)
            ->values();

        if ($datesToWarm->isEmpty()) {
            $this->info("Tidak ada tanggal historis yang eligible untuk warm cache pada rentang {$from} s/d {$to}.");

            return self::SUCCESS;
        }

        $targets = $this->resolveTargets();
        if ($targets === null) {
            return self::FAILURE;
        }

        $refresh = (bool) $this->option('refresh');
        $stats = [
            'warmed' => 0,
            'refreshed' => 0,
            'already_cached' => 0,
            'skipped_incomplete' => 0,
        ];

        $this->line(sprintf(
            'Warm cache Rekap TKS %s untuk %d tanggal dan %d target.',
            $refresh ? '(refresh mode)' : '',
            $datesToWarm->count(),
            count($targets),
        ));

        $progressBar = $this->output->createProgressBar($datesToWarm->count() * count($targets));
        $progressBar->start();

        foreach ($datesToWarm as $date) {
            foreach ($targets as $branch) {
                $wasCached = $this->snapshotService->hasCachedSnapshot($date, $branch);

                if (! $refresh && $wasCached) {
                    $stats['already_cached']++;
                    $progressBar->advance();

                    continue;
                }

                $snapshot = $this->snapshotService->warmSnapshot($date, $branch, $refresh);

                if ($snapshot === null) {
                    $stats['skipped_incomplete']++;
                    $progressBar->advance();

                    continue;
                }

                if ($refresh && $wasCached) {
                    $stats['refreshed']++;
                } else {
                    $stats['warmed']++;
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Selesai warm cache Rekap TKS untuk %s s/d %s.',
            $datesToWarm->first(),
            $datesToWarm->last(),
        ));
        $this->line('Target: ' . implode(', ', array_map(
            fn(?string $branch): string => $branch ?? 'all',
            $targets,
        )));
        $this->line("Warmed baru: {$stats['warmed']}");
        $this->line("Refreshed: {$stats['refreshed']}");
        $this->line("Sudah tercache: {$stats['already_cached']}");
        $this->line("Skip incomplete: {$stats['skipped_incomplete']}");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, string>
     */
    private function availableHistoricalDates(): Collection
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $saldoDates = SaldoNeraca::query()
            ->where('tanggal', '<=', $yesterday)
            ->distinct()
            ->orderBy('tanggal')
            ->pluck('tanggal')
            ->map(fn($date): string => Carbon::parse($date)->toDateString());

        $loanDates = LoanOutstanding::query()
            ->where('loan_date_params', '<=', $yesterday)
            ->distinct()
            ->orderBy('loan_date_params')
            ->pluck('loan_date_params')
            ->map(fn($date): string => Carbon::parse($date)->toDateString());

        return $saldoDates
            ->intersect($loanDates)
            ->sort()
            ->values();
    }

    private function resolveBoundaryDate(?string $value, string $default): string
    {
        if (blank($value)) {
            return $default;
        }

        $resolved = Carbon::parse($value)->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        return min($resolved, $yesterday);
    }

    /**
     * @return array<int, string|null>|null
     */
    private function resolveTargets(): ?array
    {
        $selectedBranches = collect((array) $this->option('branch'))
            ->filter(fn($branch): bool => filled($branch))
            ->map(fn($branch): string => trim((string) $branch))
            ->unique()
            ->values();

        $knownBranches = BranchOffice::query()
            ->orderBy('branch_code_fincloud')
            ->pluck('branch_code_fincloud');

        if ($selectedBranches->isNotEmpty()) {
            $invalidBranches = $selectedBranches->diff($knownBranches)->values();

            if ($invalidBranches->isNotEmpty()) {
                $this->error('Branch tidak dikenal: ' . $invalidBranches->implode(', '));

                return null;
            }

            return $selectedBranches->all();
        }

        return array_merge([null], $knownBranches->all());
    }
}
