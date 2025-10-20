<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordTiming extends Model
{
    protected $fillable = [
        'verse_word_timing_id',
        'word_index',
        'arabic_text',
        'start_time',
        'end_time',
        'duration',
        'confidence',
        'tajweed_info'
    ];

    protected $casts = [
        'tajweed_info' => 'array',
        'confidence' => 'decimal:2',
        'word_index' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'duration' => 'integer'
    ];

    public function verseWordTiming(): BelongsTo
    {
        return $this->belongsTo(VerseWordTiming::class);
    }

    public function scopeInTimeRange($query, $startTime, $endTime)
    {
        return $query->where('start_time', '>=', $startTime)
                    ->where('end_time', '<=', $endTime);
    }

    public function scopeByWordIndex($query, $index)
    {
        return $query->where('word_index', $index);
    }
}
