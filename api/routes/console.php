<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::useCache('file');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('epkl:cleanup-pending-accounts')
    ->daily()
    ->withoutOverlapping();

Schedule::command('epkl:sync-placement-statuses')
    ->dailyAt('00:10')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
