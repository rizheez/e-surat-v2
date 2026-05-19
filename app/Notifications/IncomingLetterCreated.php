<?php

namespace App\Notifications;

use App\Models\IncomingLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncomingLetterCreated extends Notification
{
    use Queueable;

    public function __construct(private readonly IncomingLetter $letter)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'incoming_letter_id' => $this->letter->id,
            'perihal' => $this->letter->perihal,
            'dari' => $this->letter->asal_surat,
            'instruksi' => "Surat masuk baru dengan nomor agenda {$this->letter->nomor_agenda} telah dicatat.",
            'message' => 'Surat masuk baru tersedia untuk ditinjau.',
            'status' => $this->letter->status?->value,
            'url' => route('incoming-letters.show', $this->letter),
        ];
    }
}
