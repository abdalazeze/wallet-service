<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Wallet\DuplicateIdempotencyKeyException;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Create a new wallet
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'owner_name' => 'required|string|max:255',
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ]);

        $wallet = $this->walletService->createWallet(
            $validated['owner_name'],
            $validated['currency']
        );

        return response()->json([
            'status' => 'success',
            'data' => new WalletResource($wallet),
        ], 201);
    }

    /**
     * List all wallets with optional filters
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Wallet::query();

        // Filter by owner
        if ($request->has('owner_name')) {
            $query->where('owner_name', 'like', '%' . $request->owner_name . '%');
        }

        // Filter by currency
        if ($request->has('currency')) {
            $query->where('currency', strtoupper($request->currency));
        }

        $wallets = $query->orderBy('created_at', 'desc')->paginate(15);

        return WalletResource::collection($wallets);
    }

    /**
     * Get a specific wallet
     */
    public function show(Wallet $wallet): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => new WalletResource($wallet),
        ]);
    }

    /**
     * Get wallet balance
     */
    public function balance(Wallet $wallet): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'wallet_id' => $wallet->id,
                'balance' => $wallet->balance,
                'formatted_balance' => number_format($wallet->balance / 100, 2),
                'currency' => $wallet->currency,
            ],
        ]);
    }

    /**
     * Deposit funds into wallet
     */
    public function deposit(Request $request, Wallet $wallet): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');

        try {
            $result = $this->walletService->deposit(
                $wallet,
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

    /**
     * Withdraw funds from wallet
     */
    public function withdraw(Request $request, Wallet $wallet): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');

        try {
            $result = $this->walletService->withdraw(
                $wallet,
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

    /**
     * Get wallet transaction history
     */
    public function transactions(Request $request, Wallet $wallet): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['deposit', 'withdrawal', 'transfer_debit', 'transfer_credit'])],
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $wallet->transactions();

        // Filter by type
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        // Filter by date range
        if (!empty($validated['from_date'])) {
            $query->where('created_at', '>=', $validated['from_date']);
        }

        if (!empty($validated['to_date'])) {
            $query->where('created_at', '<=', $validated['to_date']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $transactions = $query->paginate($perPage);

        return TransactionResource::collection($transactions);
    }
}
