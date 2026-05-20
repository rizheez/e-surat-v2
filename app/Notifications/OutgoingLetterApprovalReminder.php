<?php

namespace App\Notifications;

use App\Models\OutgoingLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OutgoingLetterApprovalReminder extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OutgoingLetter $letter,
        private readonly string $reminderType,
        private readonly string $reminderDate,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isRevision = $this->reminderType === 'pending_revision';

        return [
            'letter_id' => $this->letter->id,
            'perihal' => $isRevision ? 'Reminder revisi surat keluar' : 'Reminder persetujuan surat keluar',
            'message' => $this->letter->perihal,
            'instruksi' => $isRevision
                ? 'Surat ini masih menunggu perbaikan dari pembuat.'
                : 'Surat ini masih menunggu persetujuan penandatangan.',
            'dari' => $isRevision
                ? $this->letter->signatory?->name
                : $this->letter->createdBy?->name,
            'url' => $isRevision
                ? route('outgoing-letters.edit', $this->letter)
                : route('outgoing-letters.approvals'),
            'reminder_type' => $this->reminderType,
            'reminder_date' => $this->reminderDate,
        ];
    }
}
