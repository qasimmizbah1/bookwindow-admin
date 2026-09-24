<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync pending Razorpay orders every 10 minutes safely
Schedule::command('orders:sync-razorpay')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Check and update abandoned carts every 15 minutes safely
Schedule::command('carts:check-abandoned')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

