<?php

namespace Tests\Feature;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_wallet(): void
    {
        $response = $this->postJson('/api/wallets', [
            'owner_name' => 'John Doe',
            'currency' => 'USD',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'owner_name',
                    'currency',
                    'balance',
                    'formatted_balance',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'owner_name' => 'John Doe',
                    'currency' => 'USD',
                    'balance' => 0,
                ],
            ]);

        $this->assertDatabaseHas('wallets', [
            'owner_name' => 'John Doe',
            'currency' => 'USD',
            'balance' => 0,
        ]);
    }

    public function test_can_list_all_wallets(): void
    {
        Wallet::factory()->create(['owner_name' => 'Alice', 'currency' => 'USD']);
        Wallet::factory()->create(['owner_name' => 'Bob', 'currency' => 'EUR']);

        $response = $this->getJson('/api/wallets');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_wallets_by_owner(): void
    {
        Wallet::factory()->create(['owner_name' => 'Alice Smith', 'currency' => 'USD']);
        Wallet::factory()->create(['owner_name' => 'Bob Jones', 'currency' => 'EUR']);

        $response = $this->getJson('/api/wallets?owner_name=Alice');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['owner_name' => 'Alice Smith']);
    }

    public function test_can_filter_wallets_by_currency(): void
    {
        Wallet::factory()->create(['owner_name' => 'Alice', 'currency' => 'USD']);
        Wallet::factory()->create(['owner_name' => 'Bob', 'currency' => 'EUR']);
        Wallet::factory()->create(['owner_name' => 'Charlie', 'currency' => 'USD']);

        $response = $this->getJson('/api/wallets?currency=USD');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_single_wallet(): void
    {
        $wallet = Wallet::factory()->create(['owner_name' => 'John Doe', 'currency' => 'USD']);

        $response = $this->getJson("/api/wallets/{$wallet->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $wallet->id,
                    'owner_name' => 'John Doe',
                    'currency' => 'USD',
                ],
            ]);
    }

    public function test_can_get_wallet_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 10000]); // 100.00

        $response = $this->getJson("/api/wallets/{$wallet->id}/balance");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'wallet_id' => $wallet->id,
                    'balance' => 10000,
                    'formatted_balance' => '100.00',
                ],
            ]);
    }

    public function test_can_deposit_funds(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 0]);

        $response = $this->postJson("/api/wallets/{$wallet->id}/deposit", [
            'amount' => 10000, // 100.00
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'wallet_id' => $wallet->id,
                    'type' => 'deposit',
                    'amount' => 10000,
                    'new_balance' => 10000,
                ],
            ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 10000,
        ]);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 10000,
        ]);
    }

    public function test_deposit_with_idempotency_key_prevents_duplicate(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 0]);
        $idempotencyKey = 'test-key-' . uniqid();

        // First deposit
        $response1 = $this->postJson("/api/wallets/{$wallet->id}/deposit", [
            'amount' => 10000,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(200);

        // Second deposit with same key
        $response2 = $this->postJson("/api/wallets/{$wallet->id}/deposit", [
            'amount' => 10000,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(200)
            ->assertJson(['idempotent' => true]);

        // Balance should only be 10000, not 20000
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 10000,
        ]);
    }

    public function test_can_withdraw_funds(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 10000]);

        $response = $this->postJson("/api/wallets/{$wallet->id}/withdraw", [
            'amount' => 3000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'wallet_id' => $wallet->id,
                    'type' => 'withdrawal',
                    'amount' => 3000,
                    'new_balance' => 7000,
                ],
            ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 7000,
        ]);
    }

    public function test_cannot_withdraw_with_insufficient_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 5000]);

        $response = $this->postJson("/api/wallets/{$wallet->id}/withdraw", [
            'amount' => 10000,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'code' => 'INSUFFICIENT_BALANCE',
            ]);

        // Balance should remain unchanged
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 5000,
        ]);
    }

    public function test_withdrawal_with_idempotency_key_prevents_duplicate(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 10000]);
        $idempotencyKey = 'withdraw-key-' . uniqid();

        // First withdrawal
        $response1 = $this->postJson("/api/wallets/{$wallet->id}/withdraw", [
            'amount' => 3000,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(200);

        // Second withdrawal with same key
        $response2 = $this->postJson("/api/wallets/{$wallet->id}/withdraw", [
            'amount' => 3000,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(200)
            ->assertJson(['idempotent' => true]);

        // Balance should be 7000, not 4000
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 7000,
        ]);
    }

    public function test_can_get_transaction_history(): void
    {
        $wallet = Wallet::factory()->create();

        $this->postJson("/api/wallets/{$wallet->id}/deposit", ['amount' => 10000]);
        $this->postJson("/api/wallets/{$wallet->id}/withdraw", ['amount' => 3000]);

        $response = $this->getJson("/api/wallets/{$wallet->id}/transactions");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_transactions_by_type(): void
    {
        $wallet = Wallet::factory()->create();

        $this->postJson("/api/wallets/{$wallet->id}/deposit", ['amount' => 10000]);
        $this->postJson("/api/wallets/{$wallet->id}/deposit", ['amount' => 5000]);
        $this->postJson("/api/wallets/{$wallet->id}/withdraw", ['amount' => 3000]);

        $response = $this->getJson("/api/wallets/{$wallet->id}/transactions?type=deposit");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_validates_amount_is_positive(): void
    {
        $wallet = Wallet::factory()->create();

        $response = $this->postJson("/api/wallets/{$wallet->id}/deposit", [
            'amount' => -100,
        ]);

        $response->assertStatus(422);
    }

    public function test_validates_currency_format(): void
    {
        $response = $this->postJson('/api/wallets', [
            'owner_name' => 'John Doe',
            'currency' => 'US', // Invalid, should be 3 characters
        ]);

        $response->assertStatus(422);
    }
}
