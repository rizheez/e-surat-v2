<?php

namespace App\Policies;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;

class OutgoingLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view outgoing letters');
    }

    public function view(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $user->can('view outgoing letters');
    }

    public function create(User $user): bool
    {
        return $user->can('manage outgoing letters');
    }

    public function update(User $user, OutgoingLetter $outgoingLetter): bool
    {
        if (!$user->can('manage outgoing letters')) {
            return false;
        }

        return $outgoingLetter->status !== OutgoingLetterStatus::Diarsipkan || $user->hasRole('super-admin');
    }

    public function submitApproval(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->update($user, $outgoingLetter);
    }

    public function approve(User $user, OutgoingLetter $outgoingLetter): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $outgoingLetter->signatory_user_id === $user->id
            && $outgoingLetter->status === OutgoingLetterStatus::MenungguPersetujuan;
    }

    public function reject(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->approve($user, $outgoingLetter);
    }

    public function delete(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $user->can('manage outgoing letters')
            && $outgoingLetter->status !== OutgoingLetterStatus::Diarsipkan;
    }
}
