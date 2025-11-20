<?php

use App\Jobs\SendConsolidatorDailyReminders;
use App\Jobs\SendSuynlLeaderReminders;
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
->dailyAt('13:00');

// Schedule SUYNL leader reminders (grouped per leader with next lesson per student)
Schedule::call(function () {
    SendSuynlLeaderReminders::dispatch()->onQueue('mail');
})->dailyAt('13:00');

