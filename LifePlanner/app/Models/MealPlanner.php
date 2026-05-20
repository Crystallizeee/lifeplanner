<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @see LP-DB-SCHEMA-2026-001 | Health Module — meal_planners
class MealPlanner extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'meal_time',
        'description',
        'meal_name',
        'calories',
        'recipe_notes',
        'meal_type',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ── Virtual Attributes (mapped to description JSON) ──

    public function getMealNameAttribute(): string
    {
        $data = json_decode($this->description ?? '', true);
        return $data['name'] ?? $this->description ?? '';
    }

    public function setMealNameAttribute(?string $value): void
    {
        $data = json_decode($this->description ?? '', true) ?: [];
        $data['name'] = $value ?? '';
        $this->attributes['description'] = json_encode($data);
    }

    public function getCaloriesAttribute(): ?int
    {
        $data = json_decode($this->description ?? '', true);
        return isset($data['calories']) ? (int)$data['calories'] : null;
    }

    public function setCaloriesAttribute($value): void
    {
        $data = json_decode($this->description ?? '', true) ?: [];
        $data['calories'] = $value !== null ? (int)$value : null;
        $this->attributes['description'] = json_encode($data);
    }

    public function getRecipeNotesAttribute(): ?string
    {
        $data = json_decode($this->description ?? '', true);
        return $data['notes'] ?? null;
    }

    public function setRecipeNotesAttribute(?string $value): void
    {
        $data = json_decode($this->description ?? '', true) ?: [];
        $data['notes'] = $value;
        $this->attributes['description'] = json_encode($data);
    }

    public function getMealTypeAttribute(): string
    {
        return $this->meal_time ?? '';
    }

    public function setMealTypeAttribute(string $value): void
    {
        $this->attributes['meal_time'] = $value;
    }

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

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }
}
