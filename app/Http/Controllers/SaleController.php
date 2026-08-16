<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\StockTransactionResource;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class SaleController
 *
 * RESTful API controller managing sale transactions and snapshot COGS allocation.
 */
class SaleController extends Controller
{
    /**
     * Display a listing of active sale transactions with snapshot COGS and eager-loaded product details.
     */
    public function index(): AnonymousResourceCollection
    {
        $sales = StockTransaction::where('type', 'sale')
            ->whereNull('deleted_at')
            ->with('product')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return StockTransactionResource::collection($sales);
    }

    /**
     * Record a new sale transaction, allocate point-in-time WAC COGS, and return with product information.
     */
    public function store(StoreSaleRequest $request, InventoryService $inventoryService): JsonResponse
    {
        $product = Product::findOrFail($request->product_id);

        $payload = array_merge($request->validated(), [
            'type' => 'sale',
        ]);

        $transaction = $inventoryService->recordTransaction($product, $payload);
        $transaction->load('product');

        return response()->json([
            'message' => 'Sale transaction recorded successfully.',
            'data' => new StockTransactionResource($transaction),
        ], 201);
    }
}
