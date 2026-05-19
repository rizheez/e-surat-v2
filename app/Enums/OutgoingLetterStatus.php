<?php

namespace App\Enums;

enum OutgoingLetterStatus: string
{
    case Draft = 'draft';
    case MenungguPersetujuan = 'menunggu_persetujuan';
    case PerluRevisi = 'perlu_revisi';
    case Disetujui = 'disetujui';
    case Dikirim = 'dikirim';
    case Diarsipkan = 'diarsipkan';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::MenungguPersetujuan => 'Menunggu Persetujuan',
            self::PerluRevisi => 'Perlu Revisi',
            self::Disetujui => 'Disetujui',
            self::Dikirim => 'Dikirim',
            self::Diarsipkan => 'Diarsipkan',
        };
    }
}
