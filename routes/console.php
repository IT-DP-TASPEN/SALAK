<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:fetch-balance-sheet-report', [now()->subDay()->format('Y-m-d')])
    ->dailyAt('04:30')
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::call(function () {
    Artisan::call('app:fetch-balance-sheet-report');
    Artisan::call('app:update-loan-outstanding-report');

    $yesterday = now()->subDay()->format('Y-m-d');
    Artisan::call('app:fetch-atmr-data-from-mso');
    Artisan::call('app:fetch-cbr-customer-report', ['date' => $yesterday]);
    Artisan::call('app:fetch-loan-collateral-list-report', ['date' => $yesterday]);
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
