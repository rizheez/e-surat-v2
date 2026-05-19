<?php

namespace App\Services;

use App\Models\Disposition;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;

class DispositionForwardScopeService
{
    public function eligibleRecipients(User $actor, Disposition $disposition): Collection
    {
        $actor->loadMissing(['unit.parent', 'position']);
        $disposition->loadMissing(['parent', 'children']);

        if (!$actor->position || !$actor->unit) {
            return collect();
        }

        $blockedIds = $this->blockedUserIds($actor, $disposition);

        return User::query()
            ->with(['unit.parent', 'position'])
            ->where('is_active', true)
            ->whereKeyNot($actor->id)
            ->orderBy('name')
            ->get()
            ->reject(function (User $candidate) use ($actor, $blockedIds) {
                if ($blockedIds->contains($candidate->id)) {
                    return true;
                }

                return !$this->canReceiveForward($actor, $candidate);
            })
            ->values();
    }

    private function canReceiveForward(User $actor, User $candidate): bool
    {
        if (!$candidate->position || !$candidate->unit) {
            return false;
        }

        if ($candidate->position->level < $actor->position->level) {
            return false;
        }

        if ($actor->can('view all dispositions')) {
            return true;
        }

        if ($candidate->unit->is_cross_unit_target) {
            return true;
        }

        return $this->isSameOrRelatedUnit($actor->unit, $candidate->unit);
    }

    private function isSameOrRelatedUnit(Unit $actorUnit, Unit $candidateUnit): bool
    {
        if ($actorUnit->is($candidateUnit)) {
            return true;
        }

        return $this->isAncestorOf($actorUnit, $candidateUnit)
            || $this->isAncestorOf($candidateUnit, $actorUnit);
    }

    private function isAncestorOf(Unit $ancestor, Unit $unit): bool
    {
        $current = $unit->parent;

        while ($current) {
            if ($current->is($ancestor)) {
                return true;
            }

            $current->loadMissing('parent');
            $current = $current->parent;
        }

        return false;
    }

    private function blockedUserIds(User $actor, Disposition $disposition): Collection
    {
        return collect([$actor->id])
            ->merge($this->ancestorSenderIds($disposition))
            ->unique()
            ->values();
    }

    private function ancestorSenderIds(Disposition $disposition): Collection
    {
        $ids = collect();
        $current = $disposition;

        while ($current) {
            if ($current->sender_id) {
                $ids->push($current->sender_id);
            }

            $current->loadMissing('parent');
            $current = $current->parent;
        }

        return $ids;
    }
}
