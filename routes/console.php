<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Safety net: recover any survey responses whose answers were never written
// (e.g. a queue job that failed permanently or was lost).
Schedule::command('responses:reprocess')->everyFiveMinutes()->withoutOverlapping();
