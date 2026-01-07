<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['deposit', 'withdrawal', 'transfer_debit', 'transfer_credit']);
            $table->unsignedBigInteger('amount'); // Always positive, stored in minor units
            $table->foreignId('related_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->string('idempotency_key', 64)->nullable();
            $table->json('metadata')->nullable(); // Extra context if needed
            $table->timestamp('created_at')->useCurrent();

            // Indexes for performance
            $table->index('wallet_id');
            $table->index('type');
            $table->index('created_at');
            $table->unique('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
