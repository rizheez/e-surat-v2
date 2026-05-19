<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\DispositionReminderService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dispositions:send-deadline-reminders {--date=}', function (DispositionReminderService $service) {
    $runDate = $this->option('date')
        ? Carbon::parse((string) $this->option('date'))->startOfDay()
        : now()->startOfDay();

    $sent = $service->sendDueSoonReminders($runDate);

    $this->info("Reminder deadline disposisi terkirim: {$sent}");
})->purpose('Kirim reminder H-2 untuk disposisi yang mendekati batas waktu')
    ->dailyAt('07:00')
    ->withoutOverlapping();
