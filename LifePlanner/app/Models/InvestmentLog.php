<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentLog extends Model
{
    protected $fillable = [
        'investment_id',
        'user_id',
        'action',
        'quantity',
        'price',
        'notes',
        'logged_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'price' => 'decimal:2',
        'logged_at' => 'datetime',
    ];

    // ── Relationships ──

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ──

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'buy' => '🟢 Beli',
            'sell' => '🔴 Jual',
            'dividend' => '💰 Dividen',
            'price_update' => '📊 Update Harga',
            'top_up' => '➕ Top-Up',
            default => $action,
        };
    }
}
