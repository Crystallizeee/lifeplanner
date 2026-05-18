<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @see LP-DB-SCHEMA-2026-001 | Health Module — meal_planners
class MealPlanner extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'meal_time',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }
}
