<?php

namespace App\Enums;

enum IncomingLetterStatus: string
{
    case Baru = 'baru';
    case Didisposisi = 'didisposisi';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Diarsipkan = 'diarsipkan';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Didisposisi => 'Didisposisi',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Diarsipkan => 'Diarsipkan',
        };
    }
}
