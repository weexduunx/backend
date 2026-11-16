<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TafsirTranslation extends Model
{
    protected $fillable = [
        'source', 'surah', 'ayah', 'verse_key', 'text_original',
        'language', 'text_translated', 'translator', 'translated_at',
    ];

    protected $casts = [
        'translated_at' => 'datetime',
    ];
}
