<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @see LP-DB-SCHEMA-2026-001 | Health Module — weight_logs
class WeightLog extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'weight',
        'unit',
    ];

    protected $casts = [
        'date' => 'date',
        'weight' => 'decimal:2',
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

    public function scopeLatest($query)
    {
        return $query->orderByDesc('date');
    }
}
