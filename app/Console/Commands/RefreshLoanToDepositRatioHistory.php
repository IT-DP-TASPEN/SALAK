<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshLoanToDepositRatioHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:refresh-loan-to-deposit-ratio-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the cached loan to deposit ratio history data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subMonth();
        $dates = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        $loanToDepositRatioHistory = collect($dates)->mapWithKeys(function ($date) {
            return [
                $date => \App\Models\BranchOffice::konsolidasiLDR($date, false),
            ];
        });

        // Cache the result for 30 days
        Cache::put('dashboard:loan-to-deposit-ratio-history', $loanToDepositRatioHistory, now()->addDays(30));

        $this->info('Loan to deposit ratio history cache refreshed!');
    }
}
