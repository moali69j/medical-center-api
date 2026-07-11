<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Backup Schedule
|--------------------------------------------------------------------------
|
| Runs a full backup (DB + storage/app files) every day at 2:00 AM.
| Backup archives are stored in storage/app/backups/.
| Old backups are cleaned up at 2:30 AM per the retention policy in config/backup.php.
| A health check runs every Monday at 8 AM to alert if backups are stale.
|
| All output is appended to storage/logs/backup.log so you can audit
| every backup run's success or failure.
|
*/
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/backup.log'))
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::critical('[BACKUP FAILED] Daily DB backup failed. Check storage/logs/backup.log for details.');
    });

Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->appendOutputTo(storage_path('logs/backup.log'));

Schedule::command('backup:monitor')
    ->weeklyOn(1, '08:00')
    ->appendOutputTo(storage_path('logs/backup.log'));
