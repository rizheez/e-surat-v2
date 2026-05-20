<?php

namespace App\Exports;

use App\Models\LetterNumberReservation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LetterNumberReservationsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $reservations,
    ) {
    }

    public function collection(): Collection
    {
        return $this->reservations;
    }

    public function headings(): array
    {
        return [
            'Nomor Surat',
            'Tanggal Surat',
            'Kode Kategori',
            'Nama Kategori',
            'Jenis Dokumen',
            'Perihal',
            'Tujuan Surat',
            'Catatan',
            'Status',
            'Dibuat Oleh',
            'Dipakai Oleh Surat Keluar',
            'Dipakai Pada',
        ];
    }

    public function map($reservation): array
    {
        /** @var LetterNumberReservation $reservation */
        return [
            $reservation->nomor_surat,
            $reservation->tanggal_surat?->toDateString(),
            $reservation->category?->kode,
            $reservation->category?->nama,
            $reservation->jenis_dokumen,
            $reservation->perihal,
            $reservation->tujuan_surat,
            $reservation->catatan,
            $reservation->status,
            $reservation->createdBy?->name,
            $reservation->usedByOutgoingLetter?->nomor_surat_keluar,
            $reservation->used_at?->toDateTimeString(),
        ];
    }
}
