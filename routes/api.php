<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'timestamp' => now()->toIso8601String()]);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

// Protected WAC Engine Inventory Routes
Route::middleware('auth:api')->group(function () {
    // Products Catalog
    Route::get('products', [ProductController::class, 'index']);

    // Purchases
    Route::get('purchases', [PurchaseController::class, 'index']);
    Route::post('purchases', [PurchaseController::class, 'store']);

    // Sales
    Route::get('sales', [SaleController::class, 'index']);
    Route::post('sales', [SaleController::class, 'store']);

    // Historical Mutations & Soft Deletes (Bonus 2)
    Route::put('transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('transactions/{id}', [TransactionController::class, 'destroy']);
});
