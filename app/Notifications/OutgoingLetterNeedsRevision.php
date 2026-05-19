<?php

namespace App\Notifications;

use App\Models\OutgoingLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OutgoingLetterNeedsRevision extends Notification
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
            'perihal' => 'Surat keluar perlu revisi',
            'message' => $this->letter->perihal,
            'instruksi' => $this->letter->approval_note,
            'dari' => $this->letter->signatory?->name,
            'url' => route('outgoing-letters.edit', $this->letter),
        ];
    }
}
