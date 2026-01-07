<?php

namespace Tests\Feature;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_transfer_between_wallets(): void
    {
        $walletA = Wallet::factory()->create(['balance' => 10000, 'currency' => 'USD']);
        $walletB = Wallet::factory()->create(['balance' => 0, 'currency' => 'USD']);

        $response = $this->postJson('/api/transfers', [
            'source_wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'amount' => 5000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'source_wallet_id' => $walletA->id,
                    'target_wallet_id' => $walletB->id,
                    'amount' => 5000,
                    'source_new_balance' => 5000,
                    'target_new_balance' => 5000,
                ],
            ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletA->id,
            'balance' => 5000,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletB->id,
            'balance' => 5000,
        ]);

        // Check both debit and credit transactions created
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $walletA->id,
            'type' => 'transfer_debit',
            'amount' => 5000,
            'related_wallet_id' => $walletB->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $walletB->id,
            'type' => 'transfer_credit',
            'amount' => 5000,
            'related_wallet_id' => $walletA->id,
        ]);
    }

    public function test_cannot_transfer_with_insufficient_balance(): void
    {
        $walletA = Wallet::factory()->create(['balance' => 3000, 'currency' => 'USD']);
        $walletB = Wallet::factory()->create(['balance' => 0, 'currency' => 'USD']);

        $response = $this->postJson('/api/transfers', [
            'source_wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'amount' => 5000,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'code' => 'INSUFFICIENT_BALANCE',
            ]);

        // Balances should remain unchanged
        $this->assertDatabaseHas('wallets', [
            'id' => $walletA->id,
            'balance' => 3000,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletB->id,
            'balance' => 0,
        ]);
    }

    public function test_cannot_transfer_to_same_wallet(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 10000, 'currency' => 'USD']);

        $response = $this->postJson('/api/transfers', [
            'source_wallet_id' => $wallet->id,
            'target_wallet_id' => $wallet->id,
            'amount' => 5000,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_transfer_between_different_currencies(): void
    {
        $walletUSD = Wallet::factory()->create(['balance' => 10000, 'currency' => 'USD']);
        $walletEUR = Wallet::factory()->create(['balance' => 0, 'currency' => 'EUR']);

        $response = $this->postJson('/api/transfers', [
            'source_wallet_id' => $walletUSD->id,
            'target_wallet_id' => $walletEUR->id,
            'amount' => 5000,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'code' => 'CURRENCY_MISMATCH',
            ]);

        // Balances should remain unchanged
        $this->assertDatabaseHas('wallets', [
            'id' => $walletUSD->id,
            'balance' => 10000,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletEUR->id,
            'balance' => 0,
        ]);
    }

    public function test_transfer_with_idempotency_key_prevents_duplicate(): void
    {
        $walletA = Wallet::factory()->create(['balance' => 10000, 'currency' => 'USD']);
        $walletB = Wallet::factory()->create(['balance' => 0, 'currency' => 'USD']);
        $idempotencyKey = 'transfer-key-' . uniqid();

        // First transfer
        $response1 = $this->postJson('/api/transfers', [
            'source_wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'amount' => 5000,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(200);

        // Second transfer with same key
        $response2 = $this->postJson('/api/transfers', [
            'source_wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'amount' => 5000,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(200)
            ->assertJson(['idempotent' => true]);

        // Balances should reflect only one transfer
        $this->assertDatabaseHas('wallets', [
            'id' => $walletA->id,
            'balance' => 5000,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletB->id,
            'balance' => 5000,
        ]);
    }

    public function test_transfer_is_atomic(): void
    {
        $walletA = Wallet::factory()->create(['balance' => 10000, 'currency' => 'USD']);
        $walletB = Wallet::factory()->create(['balance' => 5000, 'currency' => 'USD']);

        $this->postJson('/api/transfers', [
            'source_wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'amount' => 3000,
        ]);

        // Verify double-entry: total balance across system remains constant
        $walletA->refresh();
        $walletB->refresh();

        // Total should remain 15000 (money is just moved, not created or destroyed)
        $this->assertEquals(15000, $walletA->balance + $walletB->balance);
        // Verify individual balances
        $this->assertEquals(7000, $walletA->balance);
        $this->assertEquals(8000, $walletB->balance);
    }

    public function test_validates_amount_is_positive_for_transfer(): void
    {
        $walletA = Wallet::factory()->create(['balance' => 10000, 'currency' => 'USD']);
        $walletB = Wallet::factory()->create(['balance' => 0, 'currency' => 'USD']);

        $response = $this->postJson('/api/transfers', [
            'source_wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'amount' => -100,
        ]);

        $response->assertStatus(422);
    }

    public function test_validates_wallets_exist(): void
    {
        $response = $this->postJson('/api/transfers', [
            'source_wallet_id' => 99999,
            'target_wallet_id' => 99998,
            'amount' => 5000,
        ]);

        $response->assertStatus(422);
    }
}
