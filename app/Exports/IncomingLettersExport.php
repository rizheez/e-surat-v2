<?php

namespace App\Exports;

use App\Models\IncomingLetter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class IncomingLettersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $letters,
    ) {
    }

    public function collection(): Collection
    {
        return $this->letters;
    }

    public function headings(): array
    {
        return [
            'Nomor Agenda',
            'Nomor Surat',
            'Tanggal Surat',
            'Tanggal Diterima',
            'Asal Surat',
            'Perihal',
            'Sifat',
            'Status',
            'Dicatat Oleh',
        ];
    }

    public function map($letter): array
    {
        /** @var IncomingLetter $letter */
        return [
            $letter->nomor_agenda,
            $letter->nomor_surat,
            $letter->tanggal_surat?->toDateString(),
            $letter->tanggal_diterima?->toDateString(),
            $letter->asal_surat,
            $letter->perihal,
            $letter->nature?->nama,
            $letter->status->label(),
            $letter->createdBy?->name,
        ];
    }
}
