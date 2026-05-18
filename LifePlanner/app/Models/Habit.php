<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @see LP-DB-SCHEMA-2026-001 | Habit Module — habits
class Habit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'habit_name',
        'emoji',
        'is_archived',
        'current_streak',
        'longest_streak',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'created_at' => 'datetime',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
