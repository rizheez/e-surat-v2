<?php

namespace App\Models;

use App\Enums\OutgoingLetterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingLetter extends Model
{
    protected $fillable = [
        'nomor_surat_keluar',
        'tanggal_surat',
        'tujuan_surat',
        'perihal',
        'ringkasan',
        'kategori_surat_id',
        'signatory_user_id',
        'content_mode',
        'lampiran_text',
        'kepada_text',
        'lokasi_tujuan',
        'salam_pembuka',
        'isi_surat',
        'lampiran_detail',
        'penutup_text',
        'penandatangan_jabatan',
        'penandatangan_nama',
        'tembusan_text',
        'file_path',
        'status',
        'approval_requested_at',
        'approved_at',
        'approval_note',
        'verification_token',
        'verification_token_generated_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'status' => OutgoingLetterStatus::class,
            'approval_requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'verification_token_generated_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class, 'kategori_surat_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signatory_user_id');
    }
}
