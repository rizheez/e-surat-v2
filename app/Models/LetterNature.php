<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterNature extends Model
{
    protected $fillable = ['nama', 'kode', 'level_kerahasiaan'];

    protected function casts(): array
    {
        return ['level_kerahasiaan' => 'integer'];
    }

    public function incomingLetters(): HasMany
    {
        return $this->hasMany(IncomingLetter::class, 'sifat_surat_id');
    }
}
