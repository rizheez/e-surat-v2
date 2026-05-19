<?php

namespace App\Models;

use App\Enums\IncomingLetterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomingLetter extends Model
{
    protected $fillable = [
        'nomor_agenda',
        'nomor_surat',
        'tanggal_surat',
        'tanggal_diterima',
        'asal_surat',
        'perihal',
        'ringkasan',
        'sifat_surat_id',
        'kategori_surat_id',
        'file_path',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_diterima' => 'date',
            'status' => IncomingLetterStatus::class,
        ];
    }

    public function nature(): BelongsTo
    {
        return $this->belongsTo(LetterNature::class, 'sifat_surat_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class, 'kategori_surat_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(Disposition::class);
    }
}
