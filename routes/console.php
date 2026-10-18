<?php

use App\Services\BackupService;
use Illuminate\Support\Facades\Schedule;

// Wird vom Scheduler-Container (php artisan schedule:work) ausgeführt.
// Run by the scheduler container (php artisan schedule:work).

// Nächtliches automatisches Backup (Aufbewahrung siehe Einstellungen, min. 4 Tage)
// Nightly automatic backup (retention see settings, min. 4 days)
Schedule::call(fn () => app(BackupService::class)->runScheduledBackup())
    ->dailyAt('01:30')->name('backup-daily');
