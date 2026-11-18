<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppFeedback extends Model
{
    protected $table = 'app_feedback';

    public const CATEGORIES = ['general', 'bug', 'idea', 'content'];
    public const FEATURES = ['lecture', 'ecoute', 'tafsir', 'prieres', 'enfants', 'hors_ligne'];

    protected $fillable = [
        'rating', 'category', 'liked', 'message', 'contact', 'screen', 'client_id',
        'app_version', 'build', 'platform', 'os_version', 'device', 'locale', 'place', 'sent_at',
    ];

    protected $casts = [
        'liked' => 'array',
        'rating' => 'integer',
        'sent_at' => 'datetime',
    ];
}
