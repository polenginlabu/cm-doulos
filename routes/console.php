<?php

use App\Jobs\SendConsolidatorDailyReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule consolidator email reminders
Schedule::call(function () {
    // Dispatch the job onto the "mail" queue
    SendConsolidatorDailyReminders::dispatch()->onQueue('mail');
})
    // Use everyMinute() while testing, then switch to dailyAt('8:00') for production
    ->hourly();

