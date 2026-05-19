<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveClassification extends Model
{
    protected $fillable = ['nama', 'kode', 'masa_retensi'];
}
