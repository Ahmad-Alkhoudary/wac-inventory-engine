<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\StockTransactionResource;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

/**
 * Class TransactionController
 *
 * RESTful API controller handling historical updates and soft-deletions of transactions.
 */
class TransactionController extends Controller
{
    /**
     * Update an existing transaction (or backdate it) and trigger O(K) cascading WAC recalculation.
     */
    public function update(UpdateTransactionRequest $request, int $id, InventoryService $inventoryService): JsonResponse
    {
        $transaction = StockTransaction::whereNull('deleted_at')->findOrFail($id);

        $updatedTransaction = $inventoryService->updateTransaction($transaction, $request->validated());
        $updatedTransaction->load('product');

        return response()->json([
            'message' => 'Transaction updated and timeline recalculated successfully.',
            'data' => new StockTransactionResource($updatedTransaction),
        ]);
    }

    /**
     * Soft-delete a transaction and execute retroactive timeline realignments.
     */
    public function destroy(int $id, InventoryService $inventoryService): JsonResponse
    {
        $transaction = StockTransaction::whereNull('deleted_at')->findOrFail($id);

        $inventoryService->deleteTransaction($transaction);

        return response()->json([
            'message' => 'Transaction soft-deleted and timeline recalculated successfully.',
        ]);
    }
}
