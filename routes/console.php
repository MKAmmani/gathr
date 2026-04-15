<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run the reminder sender directly so scheduled reminders do not depend on a queue worker.
Schedule::command('reminders:send')
    ->everyMinute()
    ->name('Send scheduled reminders')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reminder-scheduler.log'));
