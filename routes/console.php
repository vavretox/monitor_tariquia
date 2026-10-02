<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::command('actions:notify-overdue')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('activitylog:clean')->weekly()->withoutOverlapping();
Schedule::call(fn () => DB::table('overdue_action_notifications')->where('notified_on', '<', today()->subYear())->delete())->monthly();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
