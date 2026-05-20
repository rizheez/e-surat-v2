<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterNumberReservation extends Model
{
    protected $fillable = [
        'nomor_surat',
        'tanggal_surat',
        'kategori_surat_id',
        'jenis_dokumen',
        'perihal',
        'tujuan_surat',
        'catatan',
        'status',
        'created_by',
        'used_by_outgoing_letter_id',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'used_at' => 'datetime',
        ];
    }

    public function isConsumed(): bool
    {
        return in_array($this->status, ['used', 'used_manual'], true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class, 'kategori_surat_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedByOutgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class, 'used_by_outgoing_letter_id');
    }
}
