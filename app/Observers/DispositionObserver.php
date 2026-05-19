<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Disposition;

class DispositionObserver
{
    public function created(Disposition $disposition): void
    {
        $this->log('created', $disposition, "Disposisi dibuat untuk surat #{$disposition->incoming_letter_id}");
    }

    public function updated(Disposition $disposition): void
    {
        $this->log('updated', $disposition, "Disposisi diperbarui untuk surat #{$disposition->incoming_letter_id}", [
            'before' => $disposition->getOriginal(),
            'after' => $disposition->getChanges(),
        ]);
    }

    private function log(string $name, Disposition $disposition, string $description, array $properties = []): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'log_name' => 'disposition.'.$name,
            'description' => $description,
            'subject_type' => $disposition::class,
            'subject_id' => $disposition->id,
            'properties' => $properties ?: $disposition->toArray(),
        ]);
    }
}
