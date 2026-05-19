<?php

namespace App\Services;

use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use Illuminate\Support\Collection;

class DispositionWorkflowService
{
    public function syncAggregateStatuses(Disposition $disposition): DispositionStatus
    {
        $disposition->loadMissing(['recipients', 'children']);

        $this->syncRecipientStatusesFromChildren($disposition);

        $status = $this->resolveDispositionStatus($disposition->recipients->pluck('status'));

        if ($disposition->status !== $status) {
            $disposition->update(['status' => $status->value]);
        }

        if ($disposition->parent) {
            $this->syncAggregateStatuses($disposition->parent);
        }

        $this->syncIncomingLetterStatus($disposition->incoming_letter_id);

        return $status;
    }

    private function syncRecipientStatusesFromChildren(Disposition $disposition): void
    {
        $childrenBySender = $disposition->children->groupBy('sender_id');

        $disposition->recipients->each(function (DispositionRecipient $recipient) use ($childrenBySender) {
            $childStatuses = $childrenBySender->get($recipient->recipient_id, collect())
                ->pluck('status')
                ->map(fn ($status) => $status instanceof DispositionStatus ? $status : DispositionStatus::from((string) $status));

            if ($childStatuses->isEmpty()) {
                return;
            }

            $nextStatus = $childStatuses->every(fn (DispositionStatus $status) => $status === DispositionStatus::Selesai)
                ? DispositionStatus::Selesai
                : DispositionStatus::Diproses;

            $attributes = [
                'status' => $nextStatus->value,
                'tanggal_dibaca' => $recipient->tanggal_dibaca ?? now(),
                'tanggal_selesai' => $nextStatus === DispositionStatus::Selesai ? ($recipient->tanggal_selesai ?? now()) : null,
            ];

            if (
                $recipient->status !== $nextStatus
                || $recipient->tanggal_dibaca === null
                || ($nextStatus === DispositionStatus::Selesai && $recipient->tanggal_selesai === null)
                || ($nextStatus !== DispositionStatus::Selesai && $recipient->tanggal_selesai !== null)
            ) {
                $recipient->update($attributes);
            }
        });

        $disposition->unsetRelation('recipients');
        $disposition->load('recipients');
    }

    private function resolveDispositionStatus(Collection $statuses): DispositionStatus
    {
        $normalized = $statuses
            ->map(fn ($status) => $status instanceof DispositionStatus ? $status : DispositionStatus::from((string) $status))
            ->values();

        if ($normalized->isEmpty()) {
            return DispositionStatus::Menunggu;
        }

        if ($normalized->every(fn (DispositionStatus $status) => $status === DispositionStatus::Selesai)) {
            return DispositionStatus::Selesai;
        }

        if ($normalized->contains(DispositionStatus::Diproses) || $normalized->contains(DispositionStatus::Selesai)) {
            return DispositionStatus::Diproses;
        }

        return DispositionStatus::Menunggu;
    }

    private function syncIncomingLetterStatus(int $incomingLetterId): void
    {
        $rootStatuses = Disposition::query()
            ->where('incoming_letter_id', $incomingLetterId)
            ->whereNull('parent_disposition_id')
            ->pluck('status')
            ->map(fn ($status) => $status instanceof DispositionStatus ? $status : DispositionStatus::from((string) $status));

        $status = $this->resolveIncomingLetterStatus($rootStatuses);

        IncomingLetter::query()->whereKey($incomingLetterId)->update(['status' => $status->value]);
    }

    private function resolveIncomingLetterStatus(Collection $statuses): IncomingLetterStatus
    {
        if ($statuses->isEmpty()) {
            return IncomingLetterStatus::Baru;
        }

        if ($statuses->every(fn (DispositionStatus $status) => $status === DispositionStatus::Selesai)) {
            return IncomingLetterStatus::Selesai;
        }

        if ($statuses->contains(DispositionStatus::Diproses) || $statuses->contains(DispositionStatus::Selesai)) {
            return IncomingLetterStatus::Diproses;
        }

        return IncomingLetterStatus::Didisposisi;
    }
}
