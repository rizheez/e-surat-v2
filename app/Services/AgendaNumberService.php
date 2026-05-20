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
                ->where('nomor_agenda', 'like', "SM/%/{$year}")
                ->lockForUpdate()
                ->orderByDesc('nomor_agenda')
                ->value('nomor_agenda');

            $sequence = 1;

            if ($latest) {
                $parts = explode('/', $latest);
                $sequence = ((int) ($parts[1] ?? 0)) + 1;
            }

            return sprintf('SM/%03d/%d', $sequence, $year);
        });
    }
}
