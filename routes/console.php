<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-update accreditation statuses every day at midnight.
// Marks past-expiry as 'Expired' and within-90-days-of-expiry as 'Expiring Soon'.
Schedule::command('accreditations:update-status')->daily();
