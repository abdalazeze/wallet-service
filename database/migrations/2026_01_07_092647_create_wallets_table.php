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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name');
            $table->char('currency', 3); // ISO 4217: USD, EUR, GBP
            $table->unsignedBigInteger('balance')->default(0); // Store in minor units (cents)
            $table->timestamps();

            // Indexes for filtering
            $table->index('owner_name');
            $table->index('currency');
            $table->index(['owner_name', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
