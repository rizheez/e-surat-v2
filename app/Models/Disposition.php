<?php

namespace App\Models;

use App\Enums\DispositionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disposition extends Model
{
    protected $fillable = [
        'incoming_letter_id',
        'parent_disposition_id',
        'sender_id',
        'instruksi',
        'catatan',
        'batas_waktu',
        'status',
        'tanggal_disposisi',
    ];

    protected function casts(): array
    {
        return [
            'batas_waktu' => 'date',
            'tanggal_disposisi' => 'datetime',
            'status' => DispositionStatus::class,
        ];
    }

    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_disposition_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_disposition_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with([
            'sender',
            'recipients.recipient.unit',
            'recipients.recipient.position',
            'childrenRecursive',
        ]);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DispositionRecipient::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(DispositionFollowup::class);
    }

    public function hasBeenForwardedBy(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->children()->where('sender_id', $userId)->exists();
    }
}
