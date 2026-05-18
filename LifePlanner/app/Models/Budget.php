<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @see LP-DB-SCHEMA-2026-001 | Finance Module — budgets
class Budget extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'starting_balance',
        'is_active',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'starting_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
