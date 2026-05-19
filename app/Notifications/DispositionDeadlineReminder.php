<?php

namespace App\Notifications;

use App\Models\Disposition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DispositionDeadlineReminder extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Disposition $disposition,
        private readonly string $reminderType,
    ) {
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
            'dari' => 'Sistem',
            'instruksi' => sprintf(
                'Reminder: disposisi ini akan jatuh tempo pada %s. Mohon tindak lanjuti sebelum batas waktu.',
                $this->disposition->batas_waktu?->translatedFormat('d F Y') ?? '-',
            ),
            'message' => 'Reminder batas waktu disposisi.',
            'batas_waktu' => $this->disposition->batas_waktu?->toDateString(),
            'reminder_type' => $this->reminderType,
            'url' => route('dispositions.show', $this->disposition),
        ];
    }
}
