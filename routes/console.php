<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\DispositionReminderService;
use App\Services\OutgoingLetterReminderService;
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

Artisan::command('outgoing-letters:send-approval-reminders {--date=}', function (OutgoingLetterReminderService $service) {
    $runDate = $this->option('date')
        ? Carbon::parse((string) $this->option('date'))->startOfDay()
        : now()->startOfDay();

    $sent = $service->sendStaleReminders($runDate);

    $this->info("Reminder approval surat keluar terkirim: {$sent}");
})->purpose('Kirim reminder untuk approval atau revisi surat keluar yang tertahan')
    ->dailyAt('08:00')
    ->withoutOverlapping();
