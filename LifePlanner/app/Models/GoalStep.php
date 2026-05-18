<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @see LP-DB-SCHEMA-2026-001 | Productivity Module — goal_steps
class GoalStep extends Model
{
    protected $fillable = [
        'goal_id',
        'step_name',
        'due_date',
        'is_completed',
        'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ──

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
