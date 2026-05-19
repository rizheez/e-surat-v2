<?php

namespace App\Notifications;

use App\Models\OutgoingLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OutgoingLetterApprovalRequested extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OutgoingLetter $letter,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'perihal' => 'Permintaan persetujuan surat keluar',
            'message' => $this->letter->perihal,
            'dari' => $this->letter->createdBy?->name,
            'url' => route('outgoing-letters.approvals'),
        ];
    }
}
