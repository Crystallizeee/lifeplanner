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
        'qty',
        'is_checked',
        'week_of',
        'category',
    ];

    protected $casts = [
        'week_start' => 'date',
        'quantity' => 'decimal:2',
        'is_bought' => 'boolean',
    ];

    // ── Virtual Attributes (mapping legacy fields to schema columns) ──

    public function getQtyAttribute()
    {
        return $this->quantity;
    }

    public function setQtyAttribute($value): void
    {
        $this->attributes['quantity'] = $value;
    }

    public function getIsCheckedAttribute(): bool
    {
        return (bool)$this->is_bought;
    }

    public function setIsCheckedAttribute($value): void
    {
        $this->attributes['is_bought'] = (bool)$value;
    }

    public function getWeekOfAttribute()
    {
        return $this->week_start;
    }

    public function setWeekOfAttribute($value): void
    {
        $this->attributes['week_start'] = $value;
    }

    public function getCategoryAttribute(): string
    {
        return $this->categoryRelation?->name ?? 'Umum';
    }

    public function setCategoryAttribute(?string $value): void
    {
        if (empty($value)) {
            $this->attributes['category_id'] = null;
            return;
        }

        // Find or create category of type 'grocery' for the user
        $userId = $this->user_id ?? \Illuminate\Support\Facades\Auth::id();
        if ($userId) {
            $categoryObj = Category::firstOrCreate([
                'user_id' => $userId,
                'name' => $value,
                'type' => 'grocery',
            ]);
            $this->attributes['category_id'] = $categoryObj->id;
        }
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Rename standard category relation to avoid conflict with 'category' accessor
    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Keep original category method for backward compatibility
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
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
