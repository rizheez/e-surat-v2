<?php

namespace App\Notifications;

use App\Enums\DispositionStatus;
use App\Models\Disposition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DispositionStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Disposition $disposition,
        private readonly DispositionStatus $status,
        private readonly User $actor,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusLabel = $this->status->label();

        return [
            'disposition_id' => $this->disposition->id,
            'incoming_letter_id' => $this->disposition->incoming_letter_id,
            'perihal' => $this->disposition->incomingLetter->perihal,
            'dari' => $this->actor->name,
            'instruksi' => "Status disposisi diperbarui menjadi {$statusLabel} oleh {$this->actor->name}.",
            'message' => $this->status === DispositionStatus::Selesai
                ? 'Disposisi telah selesai ditindaklanjuti.'
                : 'Status disposisi diperbarui.',
            'status' => $this->status->value,
            'batas_waktu' => $this->disposition->batas_waktu?->toDateString(),
            'url' => route('dispositions.show', $this->disposition),
        ];
    }
}
