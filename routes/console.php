<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\DispositionReminderService;
use App\Services\OutgoingLetterReminderService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dispositions:send-deadline-reminders {--date=} {--dry-run}', function (DispositionReminderService $service) {
    $runDate = $this->option('date')
        ? Carbon::parse((string) $this->option('date'))->startOfDay()
        : now()->startOfDay();

    $dryRun = (bool) $this->option('dry-run');
    $sent = $service->sendDueSoonReminders($runDate, $dryRun);

    $message = $dryRun
        ? "Calon reminder deadline disposisi: {$sent}"
        : "Reminder deadline disposisi terkirim: {$sent}";

    $this->info($message);
})->purpose('Kirim reminder H-1 untuk disposisi yang mendekati batas waktu')
    ->dailyAt('07:00')
    ->withoutOverlapping();

Artisan::command('outgoing-letters:send-approval-reminders {--date=} {--dry-run}', function (OutgoingLetterReminderService $service) {
    $runDate = $this->option('date')
        ? Carbon::parse((string) $this->option('date'))->startOfDay()
        : now()->startOfDay();

    $dryRun = (bool) $this->option('dry-run');
    $sent = $service->sendStaleReminders($runDate, $dryRun);

    $message = $dryRun
        ? "Calon reminder approval surat keluar: {$sent}"
        : "Reminder approval surat keluar terkirim: {$sent}";

    $this->info($message);
})->purpose('Kirim reminder untuk approval atau revisi surat keluar yang tertahan')
    ->dailyAt('08:00')
    ->withoutOverlapping();
