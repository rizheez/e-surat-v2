<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterNature extends Model
{
    protected $fillable = ['nama', 'kode', 'level_kerahasiaan'];

    protected function casts(): array
    {
        return ['level_kerahasiaan' => 'integer'];
    }
}
