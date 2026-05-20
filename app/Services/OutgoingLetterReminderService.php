<?php

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Notifications\OutgoingLetterApprovalReminder;
use Carbon\CarbonInterface;

class OutgoingLetterReminderService
{
    public const PENDING_APPROVAL = 'pending_approval';
    public const PENDING_REVISION = 'pending_revision';

    public function sendStaleReminders(CarbonInterface $runDate): int
    {
        $sent = 0;
        $threshold = $runDate->copy()->subDays(2)->endOfDay();
        $reminderDate = $runDate->toDateString();

        OutgoingLetter::query()
            ->with(['createdBy', 'signatory'])
            ->where('content_mode', 'generate')
            ->where(function ($query) use ($threshold) {
                $query
                    ->where(function ($query) use ($threshold) {
                        $query->where('status', OutgoingLetterStatus::MenungguPersetujuan->value)
                            ->where('approval_requested_at', '<=', $threshold);
                    })
                    ->orWhere(function ($query) use ($threshold) {
                        $query->where('status', OutgoingLetterStatus::PerluRevisi->value)
                            ->where('updated_at', '<=', $threshold);
                    });
            })
            ->chunkById(100, function ($letters) use (&$sent, $reminderDate) {
                foreach ($letters as $letter) {
                    if ($letter->status === OutgoingLetterStatus::MenungguPersetujuan) {
                        $user = $letter->signatory;
                        $type = self::PENDING_APPROVAL;
                    } else {
                        $user = $letter->createdBy;
                        $type = self::PENDING_REVISION;
                    }

                    if (!$user || !$user->is_active) {
                        continue;
                    }

                    $alreadySent = $user->notifications()
                        ->where('type', OutgoingLetterApprovalReminder::class)
                        ->where('data->letter_id', $letter->id)
                        ->where('data->reminder_type', $type)
                        ->where('data->reminder_date', $reminderDate)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $user->notify(new OutgoingLetterApprovalReminder($letter, $type, $reminderDate));
                    $sent++;
                }
            });

        return $sent;
    }
}
