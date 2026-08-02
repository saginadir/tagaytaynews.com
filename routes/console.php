<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('news:fetch')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('news:watch-phivolcs')->everySixHours()->withoutOverlapping();
Schedule::command('seo:indexnow')->dailyAt('4:17')->withoutOverlapping();
