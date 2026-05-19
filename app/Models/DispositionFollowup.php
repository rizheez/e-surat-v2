<?php

namespace App\Models;

use App\Enums\DispositionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispositionFollowup extends Model
{
    protected $fillable = ['disposition_id', 'recipient_id', 'catatan', 'file_path', 'status'];

    protected function casts(): array
    {
        return ['status' => DispositionStatus::class];
    }

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
