<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterCategory extends Model
{
    protected $fillable = ['nama', 'kode', 'deskripsi'];

    public function outgoingLetters(): HasMany
    {
        return $this->hasMany(OutgoingLetter::class, 'kategori_surat_id');
    }
}
