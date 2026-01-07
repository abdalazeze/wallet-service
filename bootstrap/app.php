<?php

use App\Exceptions\Wallet\CurrencyMismatchException;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Exceptions\Wallet\InvalidAmountException;
use App\Exceptions\Wallet\SelfTransferException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle validation exceptions for API routes
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle wallet-specific exceptions
        $exceptions->render(function (InsufficientBalanceException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 'INSUFFICIENT_BALANCE',
            ], 422);
        });

        $exceptions->render(function (InvalidAmountException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 'INVALID_AMOUNT',
            ], 422);
        });

        $exceptions->render(function (SelfTransferException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 'SELF_TRANSFER_NOT_ALLOWED',
            ], 422);
        });

        $exceptions->render(function (CurrencyMismatchException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 'CURRENCY_MISMATCH',
            ], 422);
        });
    })->create();
