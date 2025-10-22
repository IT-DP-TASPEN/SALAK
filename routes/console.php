<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::exec(base_path() . '/saldo-neraca-updater', ['-date=' . now()->format('Y-m-d')])
    ->everyThreeHours()
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::exec(base_path() . '/loan-outstanding-updater', ['-date=' . now()->format('Y-m-d')])
    ->everyThreeHours()
    ->onOneServer()
    ->runInBackground()
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
