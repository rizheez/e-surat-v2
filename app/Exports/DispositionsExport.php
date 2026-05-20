<?php

namespace App\Exports;

use App\Models\Disposition;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DispositionsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $dispositions,
    ) {
    }

    public function collection(): Collection
    {
        return $this->dispositions;
    }

    public function headings(): array
    {
        return [
            'Nomor Agenda',
            'Perihal Surat',
            'Pengirim Disposisi',
            'Penerima',
            'Instruksi',
            'Catatan',
            'Tanggal Disposisi',
            'Batas Waktu',
            'Status',
        ];
    }

    public function map($disposition): array
    {
        /** @var Disposition $disposition */
        return [
            $disposition->incomingLetter?->nomor_agenda,
            $disposition->incomingLetter?->perihal,
            $disposition->sender?->name,
            $disposition->recipients->map(fn ($item) => $item->recipient?->name)->filter()->join(', '),
            $disposition->instruksi,
            $disposition->catatan,
            $disposition->tanggal_disposisi?->toDateTimeString(),
            $disposition->batas_waktu?->toDateString(),
            $disposition->status->label(),
        ];
    }
}
