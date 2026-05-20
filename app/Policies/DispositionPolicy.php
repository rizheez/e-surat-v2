<?php

namespace App\Policies;

use App\Models\Disposition;
use App\Models\User;

class DispositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view disposition');
    }

    public function view(User $user, Disposition $disposition): bool
    {
        return $user->can('view all dispositions')
            || $disposition->sender_id === $user->id
            || $disposition->recipients()->where('recipient_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('create disposition');
    }

    public function update(User $user, Disposition $disposition): bool
    {
        if (!$user->can('update disposition status')) {
            return false;
        }

        $recipient = $disposition->recipients()->where('recipient_id', $user->id)->first();

        if ($recipient) {
            return $recipient->status !== \App\Enums\DispositionStatus::Selesai
                && !$disposition->hasBeenForwardedBy($user);
        }

        return $disposition->sender_id === $user->id;
    }

    public function forward(User $user, Disposition $disposition): bool
    {
        $recipient = $disposition->recipients()->where('recipient_id', $user->id)->first();

        return $user->can('create disposition')
            && $recipient !== null
            && $recipient->status !== \App\Enums\DispositionStatus::Selesai
            && !$disposition->hasBeenForwardedBy($user);
    }
}
