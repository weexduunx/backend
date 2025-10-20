<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReciterProfile extends Model
{
    protected $fillable = [
        'code',
        'name',
        'average_speed',
        'pause_multiplier',
        'tajweed_style',
        'supported_verses',
        'is_active'
    ];

    protected $casts = [
        'supported_verses' => 'array',
        'is_active' => 'boolean',
        'pause_multiplier' => 'decimal:2',
        'average_speed' => 'integer'
    ];

    public function verseWordTimings(): HasMany
    {
        return $this->hasMany(VerseWordTiming::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
