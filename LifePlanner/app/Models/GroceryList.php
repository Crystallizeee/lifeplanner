<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @see LP-DB-SCHEMA-2026-001 | Health Module — grocery_lists
class GroceryList extends Model
{
    protected $fillable = [
        'user_id',
        'week_start',
        'item_name',
        'quantity',
        'unit',
        'category_id',
        'is_bought',
    ];

    protected $casts = [
        'week_start' => 'date',
        'quantity' => 'decimal:2',
        'is_bought' => 'boolean',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // ── Scopes ──

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForWeek($query, $weekStart)
    {
        return $query->whereDate('week_start', $weekStart);
    }

    public function scopeNotBought($query)
    {
        return $query->where('is_bought', false);
    }
}
