<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tafsir extends Model
{
    protected $fillable = [
        'surah_id',
        'hafiz_name',
        'audio_file_path',
        'audio_url',
        'duration_seconds',
        'file_size_bytes',
        'language',
        'description',
        'is_available'
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }
}
