<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArchivesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $archives,
    ) {
    }

    public function collection(): Collection
    {
        return $this->archives;
    }

    public function headings(): array
    {
        return [
            'Jenis Arsip',
            'Nomor',
            'Tanggal Dokumen',
            'Asal/Tujuan',
            'Perihal',
            'Kategori/Sifat',
            'Status',
            'Dicatat/Dibuat Oleh',
        ];
    }

    public function map($archive): array
    {
        return [
            $archive['jenis_arsip'],
            $archive['nomor'],
            $archive['tanggal_dokumen'],
            $archive['asal_tujuan'],
            $archive['perihal'],
            $archive['kategori_sifat'],
            $archive['status'],
            $archive['dibuat_oleh'],
        ];
    }
}
