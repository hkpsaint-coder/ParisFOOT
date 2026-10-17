<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use App\Models\AppSetting;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$refreshMinutes = Schema::hasTable('app_settings')
    ? (int) AppSetting::read('odds_fetch_minutes', config('services.odds_api.refresh_minutes', 30))
    : (int) config('services.odds_api.refresh_minutes', 30);

Schedule::command('odds:fetch')
    ->cron('*/'.max(1, min(59, $refreshMinutes)).' * * * *')
    ->withoutOverlapping();
