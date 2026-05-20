<?php

namespace App\Exports;

use App\Models\OutgoingLetter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OutgoingLettersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            'Nomor Surat',
            'Tanggal Surat',
            'Tujuan',
            'Perihal',
            'Kategori',
            'Status',
            'Penandatangan',
            'Jabatan Penandatangan',
            'Dibuat Oleh',
        ];
    }

    public function map($letter): array
    {
        /** @var OutgoingLetter $letter */
        return [
            $letter->nomor_surat_keluar,
            $letter->tanggal_surat?->toDateString(),
            $letter->tujuan_surat,
            $letter->perihal,
            $letter->category?->nama,
            $letter->status->label(),
            $letter->penandatangan_nama ?: $letter->signatory?->name,
            $letter->penandatangan_jabatan ?: $letter->signatory?->position?->nama,
            $letter->createdBy?->name,
        ];
    }
}
