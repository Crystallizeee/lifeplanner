<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @see LP-DB-SCHEMA-2026-001 | Core Module — categories
class Category extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgetAllocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class);
    }

    public function todoLists(): HasMany
    {
        return $this->hasMany(TodoList::class);
    }

    public function groceryLists(): HasMany
    {
        return $this->hasMany(GroceryList::class);
    }

    // ── Scopes ──

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
