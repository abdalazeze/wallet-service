<?php

namespace App\Services;

use App\Exceptions\Wallet\CurrencyMismatchException;
use App\Exceptions\Wallet\DuplicateIdempotencyKeyException;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Exceptions\Wallet\InvalidAmountException;
use App\Exceptions\Wallet\SelfTransferException;
use App\Models\IdempotencyLog;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class TransferService
{
    /**
     * Transfer funds between wallets atomically
     */
    public function transfer(
        Wallet $sourceWallet,
        Wallet $targetWallet,
        int $amount,
        ?string $idempotencyKey = null
    ): array {
        // Validate transfer
        $this->validateTransfer($sourceWallet, $targetWallet, $amount);

        // Check idempotency
        if ($idempotencyKey) {
            $this->checkIdempotency($idempotencyKey, [
                'source_wallet_id' => $sourceWallet->id,
                'target_wallet_id' => $targetWallet->id,
                'amount' => $amount,
            ]);
        }

        return DB::transaction(function () use ($sourceWallet, $targetWallet, $amount, $idempotencyKey) {
            // CRITICAL: Lock wallets in consistent order (ascending ID) to prevent deadlocks
            $wallets = [$sourceWallet->id => $sourceWallet, $targetWallet->id => $targetWallet];
            ksort($wallets);

            $lockedWallets = [];
            foreach ($wallets as $id => $wallet) {
                $lockedWallets[$id] = Wallet::where('id', $id)->lockForUpdate()->first();
            }

            $source = $lockedWallets[$sourceWallet->id];
            $target = $lockedWallets[$targetWallet->id];

            // Check sufficient balance
            if (!$source->hasSufficientBalance($amount)) {
                throw new InsufficientBalanceException(
                    "Insufficient balance. Available: {$source->balance}, Requested: {$amount}"
                );
            }

            // Perform transfer
            $source->decrement('balance', $amount);
            $target->increment('balance', $amount);

            // Record debit transaction
            $debitTransaction = Transaction::create([
                'wallet_id' => $source->id,
                'type' => Transaction::TYPE_TRANSFER_DEBIT,
                'amount' => $amount,
                'related_wallet_id' => $target->id,
                'idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'transfer_to' => $target->owner_name,
                    'transfer_to_wallet_id' => $target->id,
                ],
            ]);

            // Record credit transaction (no idempotency key to avoid unique constraint violation)
            $creditTransaction = Transaction::create([
                'wallet_id' => $target->id,
                'type' => Transaction::TYPE_TRANSFER_CREDIT,
                'amount' => $amount,
                'related_wallet_id' => $source->id,
                'idempotency_key' => null,
                'metadata' => [
                    'transfer_from' => $source->owner_name,
                    'transfer_from_wallet_id' => $source->id,
                ],
            ]);

            $response = [
                'transfer_id' => $debitTransaction->id,
                'source_wallet_id' => $source->id,
                'target_wallet_id' => $target->id,
                'amount' => $amount,
                'source_new_balance' => $source->fresh()->balance,
                'target_new_balance' => $target->fresh()->balance,
                'debit_transaction_id' => $debitTransaction->id,
                'credit_transaction_id' => $creditTransaction->id,
            ];

            // Store idempotency log
            if ($idempotencyKey) {
                $this->storeIdempotencyLog($idempotencyKey, $response, [
                    'source_wallet_id' => $sourceWallet->id,
                    'target_wallet_id' => $targetWallet->id,
                    'amount' => $amount,
                ]);
            }

            return $response;
        });
    }

    /**
     * Validate transfer requirements
     */
    protected function validateTransfer(Wallet $source, Wallet $target, int $amount): void
    {
        // Validate amount
        if ($amount <= 0) {
            throw new InvalidAmountException('Amount must be greater than zero');
        }

        // Prevent self-transfer
        if ($source->id === $target->id) {
            throw new SelfTransferException();
        }

        // Ensure same currency
        if ($source->currency !== $target->currency) {
            throw new CurrencyMismatchException(
                "Cannot transfer between different currencies ({$source->currency} -> {$target->currency})"
            );
        }
    }

    /**
     * Check if idempotency key already exists
     */
    protected function checkIdempotency(string $key, array $requestData): void
    {
        $requestHash = hash('sha256', json_encode($requestData));

        $existing = IdempotencyLog::where('idempotency_key', $key)->first();

        if ($existing) {
            // Verify request payload matches
            if ($existing->request_hash !== $requestHash) {
                throw new InvalidAmountException('Idempotency key reused with different request data');
            }

            // Return cached response
            throw new DuplicateIdempotencyKeyException($existing->response_data);
        }
    }

    /**
     * Store idempotency log
     */
    protected function storeIdempotencyLog(string $key, array $response, array $requestData): void
    {
        IdempotencyLog::create([
            'idempotency_key' => $key,
            'request_hash' => hash('sha256', json_encode($requestData)),
            'response_data' => $response,
        ]);
    }
}
