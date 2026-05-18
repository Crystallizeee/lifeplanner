<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investment extends Model
{
    protected $fillable = [
        'user_id',
        'asset_name',
        'asset_type',
        'quantity',
        'buy_price',
        'current_price',
        'buy_date',
        'notes',
        'is_sold',
        'sold_price',
        'sold_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'buy_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'sold_price' => 'decimal:2',
        'buy_date' => 'date',
        'sold_date' => 'date',
        'is_sold' => 'boolean',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InvestmentLog::class)->orderByDesc('logged_at');
    }

    // ── Computed Attributes ──

    /** Total cost basis = buy_price × quantity */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->buy_price * (float) $this->quantity;
    }

    /** Total market value = current_price × quantity */
    public function getTotalValueAttribute(): float
    {
        return (float) $this->current_price * (float) $this->quantity;
    }

    /** Unrealized P&L in absolute Rupiah */
    public function getUnrealizedPnlAttribute(): float
    {
        return $this->total_value - $this->total_cost;
    }

    /** Unrealized P&L as percentage */
    public function getUnrealizedPnlPercentAttribute(): float
    {
        if ($this->total_cost <= 0) return 0;
        return round(($this->unrealized_pnl / $this->total_cost) * 100, 2);
    }

    /** Realized P&L (only if sold) */
    public function getRealizedPnlAttribute(): float
    {
        if (!$this->is_sold || !$this->sold_price) return 0;
        return ((float) $this->sold_price - (float) $this->buy_price) * (float) $this->quantity;
    }

    // ── Scopes ──

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_sold', false);
    }

    public function scopeSold($query)
    {
        return $query->where('is_sold', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('asset_type', $type);
    }

    // ── Helpers ──

    public static function assetTypeIcon(string $type): string
    {
        return match ($type) {
            'saham' => '📊',
            'reksadana' => '📈',
            'crypto' => '₿',
            'emas' => '🥇',
            'deposito' => '🏦',
            'properti' => '🏠',
            default => '💼',
        };
    }

    public static function assetTypeLabel(string $type): string
    {
        return match ($type) {
            'saham' => 'Saham',
            'reksadana' => 'Reksa Dana',
            'crypto' => 'Crypto',
            'emas' => 'Emas',
            'deposito' => 'Deposito',
            'properti' => 'Properti',
            default => 'Lainnya',
        };
    }
}
