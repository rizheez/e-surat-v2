<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterTemplate extends Model
{
    protected $fillable = [
        'nama',
        'kategori_surat_id',
        'tujuan_surat',
        'perihal',
        'ringkasan',
        'lampiran_text',
        'kepada_text',
        'lokasi_tujuan',
        'salam_pembuka',
        'isi_surat',
        'lampiran_detail',
        'penutup_text',
        'tembusan_text',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class, 'kategori_surat_id');
    }
}
