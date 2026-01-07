<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Wallet\DuplicateIdempotencyKeyException;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    /**
     * Transfer funds between wallets
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_wallet_id' => 'required|integer|exists:wallets,id',
            'target_wallet_id' => 'required|integer|exists:wallets,id|different:source_wallet_id',
            'amount' => 'required|integer|min:1',
        ]);

        $sourceWallet = Wallet::findOrFail($validated['source_wallet_id']);
        $targetWallet = Wallet::findOrFail($validated['target_wallet_id']);

        $idempotencyKey = $request->header('Idempotency-Key');

        try {
            $result = $this->transferService->transfer(
                $sourceWallet,
                $targetWallet,
                $validated['amount'],
                $idempotencyKey
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (DuplicateIdempotencyKeyException $e) {
            return response()->json([
                'status' => 'success',
                'data' => $e->cachedResponse,
                'idempotent' => true,
            ]);
        }
    }
}
