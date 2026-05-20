<?php

namespace App\Services;

use App\Models\LetterCategory;
use App\Models\LetterNumberReservation;
use App\Models\OutgoingLetter;
use Carbon\CarbonInterface;

class OutgoingLetterNumberService
{
    public function generate(LetterCategory $category, CarbonInterface $date, ?OutgoingLetter $letter = null): string
    {
        $sequence = $letter ? $this->resolveExistingSequence($letter) : null;
        $sequence ??= $this->nextSequence($date, $letter);

        return sprintf(
            '%s/%d/UNU-KT/%02d/%d',
            $category->kode,
            $sequence,
            (int) $date->format('n'),
            (int) $date->format('Y'),
        );
    }

    private function nextSequence(CarbonInterface $date, ?OutgoingLetter $letter = null): int
    {
        $outgoingQuery = OutgoingLetter::query()
            ->whereYear('tanggal_surat', $date->year);

        if ($letter) {
            $outgoingQuery->whereKeyNot($letter->id);
        }

        $outgoingMax = $outgoingQuery
            ->get(['id', 'nomor_surat_keluar'])
            ->map(fn (OutgoingLetter $item) => $this->extractSequence($item->nomor_surat_keluar))
            ->filter()
            ->max() ?? 0;

        $reservationMax = LetterNumberReservation::query()
            ->whereYear('tanggal_surat', $date->year)
            ->get(['id', 'nomor_surat'])
            ->map(fn (LetterNumberReservation $item) => $this->extractSequence($item->nomor_surat))
            ->filter()
            ->max() ?? 0;

        return max($outgoingMax, $reservationMax) + 1;
    }

    private function resolveExistingSequence(OutgoingLetter $letter): ?int
    {
        return $this->extractSequence($letter->nomor_surat_keluar);
    }

    private function extractSequence(?string $number): ?int
    {
        if (!$number) {
            return null;
        }

        if (!preg_match('/^[^\/]+\/(\d+)\/UNU-KT\/\d{1,2}\/\d{4}$/', $number, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
