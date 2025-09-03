<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshCashRatioHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:refresh-cash-ratio-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the cached cash ratio history data';

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
        $cashRatioHistory = collect($dates)->mapWithKeys(function ($date) {
            return [
                $date => \App\Models\BranchOffice::konsolidasiCashRatio($date, false, true),
            ];
        });

        // Cache the result for 30 days
        Cache::put('dashboard:cash-ratio-history', $cashRatioHistory, now()->addDays(30));

        $this->info('Cash ratio history cache refreshed!');
    }
}
