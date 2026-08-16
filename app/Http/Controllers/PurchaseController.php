<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\StockTransactionResource;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class PurchaseController
 *
 * RESTful API controller managing incoming purchase transactions.
 */
class PurchaseController extends Controller
{
    /**
     * Display a listing of active purchase transactions with eager-loaded product details.
     */
    public function index(): AnonymousResourceCollection
    {
        $purchases = StockTransaction::where('type', 'purchase')
            ->whereNull('deleted_at')
            ->with('product')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return StockTransactionResource::collection($purchases);
    }

    /**
     * Record a new purchase transaction for a product and return with product information.
     */
    public function store(StorePurchaseRequest $request, InventoryService $inventoryService): JsonResponse
    {
        $product = Product::findOrFail($request->product_id);

        $payload = array_merge($request->validated(), [
            'type' => 'purchase',
        ]);

        $transaction = $inventoryService->recordTransaction($product, $payload);
        $transaction->load('product');

        return response()->json([
            'message' => 'Purchase transaction recorded successfully.',
            'data' => new StockTransactionResource($transaction),
        ], 201);
    }
}
