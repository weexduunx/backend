<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surah extends Model
{
    protected $fillable = [
        'number',
        'name_arabic',
        'name_french',
        'name_transliteration',
        'verses_count',
        'revelation_type',
        'revelation_place',
        'description'
    ];

    public function verses(): HasMany
    {
        return $this->hasMany(Verse::class);
    }

    public function tafsirs(): HasMany
    {
        return $this->hasMany(Tafsir::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
