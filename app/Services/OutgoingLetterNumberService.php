<?php

namespace App\Services;

use App\Models\LetterCategory;
use App\Models\OutgoingLetter;
use Carbon\CarbonInterface;

class OutgoingLetterNumberService
{
    public function generate(LetterCategory $category, CarbonInterface $date, ?OutgoingLetter $letter = null): string
    {
        $sequence = $letter ? $this->resolveExistingSequence($letter) : null;
        $sequence ??= $this->nextSequence($date, $letter);

        return sprintf(
            '%s/%d/UNU-KT/%d/%d',
            $category->kode,
            $sequence,
            (int) $date->format('n'),
            (int) $date->format('Y'),
        );
    }

    private function nextSequence(CarbonInterface $date, ?OutgoingLetter $letter = null): int
    {
        $query = OutgoingLetter::query()
            ->whereYear('tanggal_surat', $date->year);

        if ($letter) {
            $query->whereKeyNot($letter->id);
        }

        return $query
            ->get(['id', 'nomor_surat_keluar'])
            ->map(fn (OutgoingLetter $item) => $this->extractSequence($item->nomor_surat_keluar))
            ->filter()
            ->max() + 1;
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
