<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @see LP-DB-SCHEMA-2026-001 | Habit Module — habit_logs
class HabitLog extends Model
{
    protected $fillable = [
        'habit_id',
        'date',
        'is_checked',
    ];

    protected $casts = [
        'date' => 'date',
        'is_checked' => 'boolean',
    ];

    // ── Relationships ──

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
