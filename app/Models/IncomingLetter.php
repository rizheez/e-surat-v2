<?php

namespace App\Models;

use App\Enums\IncomingLetterStatus;
use Illuminate\Database\Eloquent\Builder;
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(Disposition::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('view all incoming letters')) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->where('created_by', $user->id)
                ->orWhereHas('dispositions', function (Builder $dispositions) use ($user) {
                    $dispositions->where('sender_id', $user->id)
                        ->orWhereHas('recipients', fn (Builder $recipients) => $recipients->where('recipient_id', $user->id));
                });
        });
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->can('view all incoming letters')) {
            return true;
        }

        if ($this->created_by === $user->id) {
            return true;
        }

        return $this->dispositions()
            ->where(function (Builder $query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhereHas('recipients', fn (Builder $recipients) => $recipients->where('recipient_id', $user->id));
            })
            ->exists();
    }
}
