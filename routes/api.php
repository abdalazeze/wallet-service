<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Health check
Route::get('/health', [HealthController::class, 'check']);

// Wallet routes
Route::prefix('wallets')->group(function () {
    // Wallet CRUD
    Route::get('/', [WalletController::class, 'index']);
    Route::post('/', [WalletController::class, 'store']);
    Route::get('/{wallet}', [WalletController::class, 'show']);

    // Wallet operations
    Route::get('/{wallet}/balance', [WalletController::class, 'balance']);
    Route::post('/{wallet}/deposit', [WalletController::class, 'deposit']);
    Route::post('/{wallet}/withdraw', [WalletController::class, 'withdraw']);

    // Transaction history
    Route::get('/{wallet}/transactions', [WalletController::class, 'transactions']);
});

// Transfer routes
Route::post('/transfers', [TransferController::class, 'store']);
