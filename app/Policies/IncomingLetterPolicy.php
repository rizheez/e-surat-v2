<?php

namespace App\Policies;

use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\User;

class IncomingLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view incoming letters');
    }

    public function view(User $user, IncomingLetter $letter): bool
    {
        if (!$user->can('view incoming letters')) {
            return false;
        }

        if ($letter->nature && $letter->nature->level_kerahasiaan > 0) {
            return $user->can('view confidential letters');
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create incoming letters');
    }

    public function update(User $user, IncomingLetter $letter): bool
    {
        if (!$user->can('update incoming letters')) {
            return false;
        }

        return $letter->status !== IncomingLetterStatus::Diarsipkan || $user->hasRole('super-admin');
    }

    public function delete(User $user, IncomingLetter $letter): bool
    {
        return $user->can('delete incoming letters') && $letter->status !== IncomingLetterStatus::Diarsipkan;
    }
}
