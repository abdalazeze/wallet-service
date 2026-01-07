<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public $timestamps = false; // Using only created_at

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'related_wallet_id',
        'idempotency_key',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Transaction types constants
     */
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_TRANSFER_DEBIT = 'transfer_debit';
    public const TYPE_TRANSFER_CREDIT = 'transfer_credit';

    /**
     * Get the wallet that owns the transaction
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the related wallet (for transfers)
     */
    public function relatedWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'related_wallet_id');
    }

    /**
     * Check if transaction is a transfer
     */
    public function isTransfer(): bool
    {
        return in_array($this->type, [self::TYPE_TRANSFER_DEBIT, self::TYPE_TRANSFER_CREDIT]);
    }
}
