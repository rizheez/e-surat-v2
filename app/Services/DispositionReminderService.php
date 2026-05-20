<?php

namespace App\Services;

use App\Enums\DispositionStatus;
use App\Models\DispositionRecipient;
use App\Notifications\DispositionDeadlineReminder;
use Carbon\CarbonInterface;

class DispositionReminderService
{
    public const H1_REMINDER = 'deadline_h1';

    public function sendDueSoonReminders(CarbonInterface $runDate, bool $dryRun = false): int
    {
        $targetDate = $runDate->copy()->addDay()->toDateString();
        $reminderDate = $runDate->toDateString();
        $sent = 0;

        DispositionRecipient::query()
            ->with(['disposition.incomingLetter', 'recipient'])
            ->whereNotIn('status', [
                DispositionStatus::Selesai->value,
            ])
            ->whereHas('recipient', fn ($query) => $query->where('is_active', true))
            ->whereHas('disposition', function ($query) use ($targetDate) {
                $query
                    ->whereDate('batas_waktu', $targetDate)
                    ->whereNotIn('status', [
                        DispositionStatus::Selesai->value,
                    ]);
            })
            ->chunkById(100, function ($recipients) use (&$sent, $dryRun, $reminderDate) {
                foreach ($recipients as $recipient) {
                    $user = $recipient->recipient;

                    if (!$user || !$recipient->disposition) {
                        continue;
                    }

                    $alreadySent = $user->notifications()
                        ->where('type', DispositionDeadlineReminder::class)
                        ->where('data->disposition_id', $recipient->disposition_id)
                        ->where('data->reminder_type', self::H1_REMINDER)
                        ->where('data->reminder_date', $reminderDate)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    if (!$dryRun) {
                        $user->notify(new DispositionDeadlineReminder(
                            $recipient->disposition,
                            self::H1_REMINDER,
                            $reminderDate,
                        ));
                    }

                    $sent++;
                }
            });

        return $sent;
    }
}
