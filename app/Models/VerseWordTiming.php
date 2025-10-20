<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerseWordTiming extends Model
{
    protected $fillable = [
        'verse_id',
        'reciter_profile_id',
        'total_duration',
        'words_data',
        'source',
        'accuracy',
        'metadata'
    ];

    protected $casts = [
        'words_data' => 'array',
        'metadata' => 'array',
        'accuracy' => 'decimal:2',
        'total_duration' => 'integer'
    ];

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class);
    }

    public function reciterProfile(): BelongsTo
    {
        return $this->belongsTo(ReciterProfile::class);
    }

    public function wordTimings(): HasMany
    {
        return $this->hasMany(WordTiming::class);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByReciter($query, $reciterCode)
    {
        return $query->whereHas('reciterProfile', function($q) use ($reciterCode) {
            $q->where('code', $reciterCode);
        });
    }
}
