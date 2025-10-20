<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Verse extends Model
{
    protected $fillable = [
        'surah_id',
        'verse_number',
        'global_number',
        'text_arabic',
        'text_french',
        'text_transliteration',
        'juz',
        'hizb',
        'rub',
        'page'
    ];

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function verseWordTimings(): HasMany
    {
        return $this->hasMany(VerseWordTiming::class);
    }
}
