<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @see LP-DB-SCHEMA-2026-001 | Finance Module — savings_goals
class SavingsGoal extends Model
{
    protected $fillable = [
        'user_id',
        'goal_name',
        'target_amount',
        'current_saved',
        'target_date',
        'is_achieved',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_saved' => 'decimal:2',
        'target_date' => 'date',
        'is_achieved' => 'boolean',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ── Computed ──

    public function getProgressPercentAttribute(): float
    {
        if ($this->target_amount <= 0) return 0;
        return min(100, round(($this->current_saved / $this->target_amount) * 100, 2));
    }
}
