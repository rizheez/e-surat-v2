<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Notifications\IncomingLetterCreated;

class IncomingLetterObserver
{
    public function created(IncomingLetter $letter): void
    {
        $this->log('created', $letter, "Surat masuk dibuat: {$letter->nomor_agenda}");

        $letter->loadMissing(['createdBy', 'nature']);

        $recipients = User::permission('view all incoming letters')
            ->where('is_active', true)
            ->when($letter->created_by, fn ($query) => $query->whereKeyNot($letter->created_by))
            ->get();

        if ($letter->nature && $letter->nature->level_kerahasiaan > 0) {
            $recipients = $recipients->filter(fn (User $user) => $user->can('view confidential letters'));
        }

        $recipients->each(fn (User $user) => $user->notify(new IncomingLetterCreated($letter)));
    }

    public function updated(IncomingLetter $letter): void
    {
        $this->log('updated', $letter, "Surat masuk diperbarui: {$letter->nomor_agenda}", [
            'before' => $letter->getOriginal(),
            'after' => $letter->getChanges(),
        ]);
    }

    public function deleted(IncomingLetter $letter): void
    {
        $this->log('deleted', $letter, "Surat masuk dihapus: {$letter->nomor_agenda}");
    }

    private function log(string $name, IncomingLetter $letter, string $description, array $properties = []): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'log_name' => 'incoming_letter.'.$name,
            'description' => $description,
            'subject_type' => $letter::class,
            'subject_id' => $letter->id,
            'properties' => $properties ?: $letter->toArray(),
        ]);
    }
}
