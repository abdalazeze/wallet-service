<?php

namespace App\Services;

use App\Exceptions\Wallet\DuplicateIdempotencyKeyException;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Exceptions\Wallet\InvalidAmountException;
use App\Models\IdempotencyLog;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Create a new wallet
     */
    public function createWallet(string $ownerName, string $currency): Wallet
    {
        return Wallet::create([
            'owner_name' => $ownerName,
            'currency' => strtoupper($currency),
            'balance' => 0,
        ]);
    }

    /**
     * Deposit funds into a wallet with idempotency
     */
    public function deposit(Wallet $wallet, int $amount, ?string $idempotencyKey = null): array
    {
        $this->validateAmount($amount);

        // Check idempotency
        if ($idempotencyKey) {
            $this->checkIdempotency($idempotencyKey, ['wallet_id' => $wallet->id, 'amount' => $amount]);
        }

        return DB::transaction(function () use ($wallet, $amount, $idempotencyKey) {
            // Lock wallet for update to prevent race conditions
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            // Update balance
            $wallet->increment('balance', $amount);

            // Record transaction
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_DEPOSIT,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);

            $response = [
                'transaction_id' => $transaction->id,
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'amount' => $amount,
                'new_balance' => $wallet->fresh()->balance,
            ];

            // Store idempotency log
            if ($idempotencyKey) {
                $this->storeIdempotencyLog($idempotencyKey, $response, [
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                ]);
            }

            return $response;
        });
    }

    /**
     * Withdraw funds from a wallet with idempotency
     */
    public function withdraw(Wallet $wallet, int $amount, ?string $idempotencyKey = null): array
    {
        $this->validateAmount($amount);

        // Check idempotency
        if ($idempotencyKey) {
            $this->checkIdempotency($idempotencyKey, ['wallet_id' => $wallet->id, 'amount' => $amount]);
        }

        return DB::transaction(function () use ($wallet, $amount, $idempotencyKey) {
            // Lock wallet for update
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            // Check sufficient balance
            if (!$wallet->hasSufficientBalance($amount)) {
                throw new InsufficientBalanceException(
                    "Insufficient balance. Available: {$wallet->balance}, Requested: {$amount}"
                );
            }

            // Update balance
            $wallet->decrement('balance', $amount);

            // Record transaction
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_WITHDRAWAL,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);

            $response = [
                'transaction_id' => $transaction->id,
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'new_balance' => $wallet->fresh()->balance,
            ];

            // Store idempotency log
            if ($idempotencyKey) {
                $this->storeIdempotencyLog($idempotencyKey, $response, [
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                ]);
            }

            return $response;
        });
    }

    /**
     * Validate amount is positive integer
     */
    protected function validateAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidAmountException('Amount must be greater than zero');
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
