<?php

namespace App\Notifications;

use App\Models\Disposition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class DispositionCreated extends Notification
{
    use Queueable;

    public function __construct(private readonly Disposition $disposition)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'disposition_id' => $this->disposition->id,
            'incoming_letter_id' => $this->disposition->incoming_letter_id,
            'perihal' => $this->disposition->incomingLetter->perihal,
            'dari' => $this->disposition->sender->name,
            'instruksi' => Str::limit($this->disposition->instruksi, 120),
            'batas_waktu' => $this->disposition->batas_waktu?->toDateString(),
            'url' => route('dispositions.show', $this->disposition),
        ];
    }
}
