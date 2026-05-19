<?php

namespace App\Services;

use App\Enums\DispositionStatus;
use App\Models\DispositionRecipient;
use App\Notifications\DispositionDeadlineReminder;
use Carbon\CarbonInterface;

class DispositionReminderService
{
    public const H2_REMINDER = 'deadline_h2';

    public function sendDueSoonReminders(CarbonInterface $runDate): int
    {
        $targetDate = $runDate->copy()->addDays(2)->toDateString();
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
            ->chunkById(100, function ($recipients) use (&$sent) {
                foreach ($recipients as $recipient) {
                    $user = $recipient->recipient;

                    if (!$user || !$recipient->disposition) {
                        continue;
                    }

                    $alreadySent = $user->notifications()
                        ->where('type', DispositionDeadlineReminder::class)
                        ->where('data->disposition_id', $recipient->disposition_id)
                        ->where('data->reminder_type', self::H2_REMINDER)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $user->notify(new DispositionDeadlineReminder(
                        $recipient->disposition,
                        self::H2_REMINDER,
                    ));

                    $sent++;
                }
            });

        return $sent;
    }
}
