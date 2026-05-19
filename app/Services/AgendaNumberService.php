<?php

namespace App\Services;

use App\Models\IncomingLetter;
use Illuminate\Support\Facades\DB;

class AgendaNumberService
{
    public function generate(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($year) {
            $latest = IncomingLetter::query()
                ->where('nomor_agenda', 'like', "{$year}/%")
                ->lockForUpdate()
                ->orderByDesc('nomor_agenda')
                ->value('nomor_agenda');

            $sequence = $latest ? ((int) substr($latest, -3)) + 1 : 1;

            return sprintf('%d/%03d', $year, $sequence);
        });
    }
}
