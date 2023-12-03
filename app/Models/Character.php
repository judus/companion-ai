<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'age',
        'gender',
        'bio',
        'occupation',
        'traits',
        'interests',
        'quirks',
        'location',
        'dialogue_style',
        'prompt',
        'status',
        'image_url',
        'is_public',
        'happiness',
        'interest',
        'romantic_attachment',
        'sadness',
        'frustration',
        'fear',
        'surprise',
        'trust',
        'confidence',
        'loneliness',
        'confusion',

    ];

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function getImageUrlAttribute($value)
    {
        return $value ? Storage::disk('gcs')->url($value) : null;
    }
}
