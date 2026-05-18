<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @see LP-DB-SCHEMA-2026-001 | Productivity Module — goals
class Goal extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'why',
        'challenges',
        'reward',
        'status',
        'progress_pct',
        'due_date',
    ];

    protected $casts = [
        'progress_pct' => 'decimal:2',
        'due_date' => 'date',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(GoalStep::class)->orderBy('sort_order');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Computed ──

    public function recalculateProgress(): void
    {
        $total = $this->steps()->count();
        if ($total === 0) {
            $this->update(['progress_pct' => 0]);
            return;
        }
        $completed = $this->steps()->where('is_completed', true)->count();
        $this->update(['progress_pct' => round(($completed / $total) * 100, 2)]);
    }
}
