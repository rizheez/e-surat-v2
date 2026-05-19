<?php

namespace App\Models;

use App\Enums\DispositionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispositionRecipient extends Model
{
    protected $fillable = [
        'disposition_id',
        'recipient_id',
        'unit_id',
        'status',
        'tanggal_dibaca',
        'tanggal_selesai',
    ];

    protected function casts(): array
    {
        return [
            'status' => DispositionStatus::class,
            'tanggal_dibaca' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
