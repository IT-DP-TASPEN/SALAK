<?php

use App\Models\LoanOutstanding;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $yesterday = now()->subDay()->format('Y-m-d');
    Artisan::call('app:fetch-balance-sheet-report', ['date' => $yesterday]);

    // delete all yesterday's LoanOutstanding data
    LoanOutstanding::where('loan_date_params', $yesterday)->delete();
    Artisan::call('app:fetch-loan-outstanding-report', ['date' => $yesterday]);
})
    ->dailyAt('04:00')
    ->name('Revalidate Balance Sheet and Loan Outstanding Data')
    ->onOneServer()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::call(function () {
    Artisan::call('app:fetch-balance-sheet-report');
    Artisan::call('app:update-loan-outstanding-report');

    $yesterday = now()->subDay()->format('Y-m-d');
    Artisan::call('app:fetch-atmr-data-from-mso');
    Artisan::call('app:fetch-cbr-customer-report', ['date' => $yesterday]);
    Artisan::call('app:fetch-loan-collateral-list', ['date' => $yesterday]);
})
    ->everyThreeHours()
    ->name('Fetch Daily Financial Reports')
    ->onOneServer()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('cache:refresh-cash-ratio-history')
    ->dailyAt('05:00')
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('cache:refresh-loan-to-deposit-ratio-history')
    ->dailyAt('05:00')
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('cache:warm-rekap-tks --to=' . now()->subDay()->format('Y-m-d'))
    ->dailyAt('05:10')
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));
