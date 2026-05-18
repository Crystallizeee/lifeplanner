<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @see LP-DB-SCHEMA-2026-001 | Finance Module — transactions
class Transaction extends Model
{
    protected $fillable = [
        'budget_id',
        'user_id',
        'category_id',
        'savings_goal_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'due_date',
        'status',
        'is_recurring',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'due_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    // ── Scopes ──

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeBills($query)
    {
        return $query->where('type', 'bill');
    }

    public function scopeInPeriod($query, $budgetId)
    {
        return $query->where('budget_id', $budgetId);
    }
}
