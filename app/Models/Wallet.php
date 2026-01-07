<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_name',
        'currency',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(int $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get formatted balance (convert from minor units to major units)
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance / 100, 2);
    }
}
