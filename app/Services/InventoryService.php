<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Class InventoryService
 *
 * Core domain service responsible for recording, updating, and soft-deleting
 * inventory ledger transactions, and executing O(K) cascading Weighted Average
 * Cost (WAC) recalculations with BCMath 4-decimal precision arithmetic.
 */
class InventoryService
{
    /**
     * Record a new Purchase or Sale transaction for a product and trigger WAC recalculation.
     *
     * @param  Product  $product Target product
     * @param  array  $transactionData Transaction payload (type, quantity, transaction_date, unit_cost, unit_price)
     * @return StockTransaction Freshly recomputed stock transaction
     *
     * @throws InsufficientStockException If sale quantity exceeds running stock
     */
    public function recordTransaction(Product $product, array $transactionData): StockTransaction
    {
        return DB::transaction(function () use ($product, $transactionData) {
            // Acquire pessimistic row lock on product to prevent race conditions
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            // Create baseline transaction record
            $stockTransaction = StockTransaction::create([
                'product_id' => $lockedProduct->id,
                'transaction_date' => $transactionData['transaction_date'],
                'type' => $transactionData['type'],
                'quantity' => $transactionData['quantity'],
                'unit_cost' => $transactionData['unit_cost'] ?? null,
                'unit_price' => $transactionData['unit_price'] ?? null,
                'cogs_unit_cost' => null,
                'total_cogs' => null,
                'running_qty' => 0,
                'running_total_value' => '0.0000',
            ]);

            // Execute O(K) cascading recomputation from transaction date
            $this->recomputeFromDate($lockedProduct, $transactionData['transaction_date']);

            return $stockTransaction->fresh();
        });
    }

    /**
     * Update an existing transaction (or backdate it) and recalculate affected timeline.
     *
     * @param  StockTransaction  $transaction Existing transaction to modify
     * @param  array  $transactionData Updated attributes (type, quantity, transaction_date, unit_cost, unit_price)
     * @return StockTransaction Updated and recomputed transaction
     *
     * @throws InsufficientStockException If modification causes historical negative stock
     */
    public function updateTransaction(StockTransaction $transaction, array $transactionData): StockTransaction
    {
        return DB::transaction(function () use ($transaction, $transactionData) {
            // Acquire pessimistic row lock on product
            $lockedProduct = Product::where('id', $transaction->product_id)->lockForUpdate()->firstOrFail();

            $originalDate = $transaction->transaction_date->format('Y-m-d');
            $newDate = isset($transactionData['transaction_date'])
                ? (string) $transactionData['transaction_date']
                : $originalDate;

            // Determine earliest affected date in historical timeline
            $earliestAffectedDate = min($originalDate, $newDate);

            // Update transaction attributes
            $transaction->update($transactionData);

            // Execute O(K) cascading recomputation from earliest affected date
            $this->recomputeFromDate($lockedProduct, $earliestAffectedDate);

            return $transaction->fresh();
        });
    }

    /**
     * Soft-delete a transaction and recompute downstream historical WAC ledger entries.
     *
     * @param  StockTransaction  $transaction Target transaction to soft-delete
     *
     * @throws InsufficientStockException If deletion causes historical negative stock
     */
    public function deleteTransaction(StockTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Acquire pessimistic row lock on product
            $lockedProduct = Product::where('id', $transaction->product_id)->lockForUpdate()->firstOrFail();

            $deletedDate = $transaction->transaction_date->format('Y-m-d');

            // Soft delete record
            $transaction->delete();

            // Execute O(K) cascading recomputation from deletion date
            $this->recomputeFromDate($lockedProduct, $deletedDate);
        });
    }

    /**
     * Execute single-pass O(K) chronological recomputation of WAC and stock balances
     * for all active downstream transactions starting from a given historical date.
     *
     * @param  Product  $product Target product
     * @param  string  $startDate Earliest affected date (YYYY-MM-DD)
     *
     * @throws InsufficientStockException If any historical state drops below zero stock
     */
    public function recomputeFromDate(Product $product, string $startDate): void
    {
        // O(1) Prior Baseline Lookup: fetch latest active transaction prior to startDate
        $priorTransaction = StockTransaction::where('product_id', $product->id)
            ->where('transaction_date', '<', $startDate)
            ->whereNull('deleted_at')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $runningQuantity = $priorTransaction ? (int) $priorTransaction->running_qty : 0;
        $runningTotalValue = $priorTransaction ? (string) $priorTransaction->running_total_value : '0.0000';

        // O(K) Downstream Query: fetch all active non-deleted transactions from startDate onward
        $downstreamTransactions = StockTransaction::where('product_id', $product->id)
            ->where('transaction_date', '>=', $startDate)
            ->whereNull('deleted_at')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($downstreamTransactions as $currentTx) {
            if ($currentTx->type === 'purchase') {
                $purchaseCost = bcmul((string) $currentTx->quantity, (string) $currentTx->unit_cost, 4);
                $runningQuantity += (int) $currentTx->quantity;
                $runningTotalValue = bcadd($runningTotalValue, $purchaseCost, 4);

                $currentTx->update([
                    'cogs_unit_cost' => null,
                    'total_cogs' => null,
                    'running_qty' => $runningQuantity,
                    'running_total_value' => $runningTotalValue,
                ]);
            } elseif ($currentTx->type === 'sale') {
                // Non-Negative Stock Invariant Guardrail
                if ($runningQuantity < (int) $currentTx->quantity) {
                    throw new InsufficientStockException(
                        "Transaction rejected: Insufficient stock on {$currentTx->transaction_date->format('Y-m-d')}. ".
                        "Available: {$runningQuantity}, Requested: {$currentTx->quantity}."
                    );
                }

                // Point-in-time WAC Unit Cost Calculation
                $wacUnitCost = $runningQuantity > 0
                    ? bcdiv($runningTotalValue, (string) $runningQuantity, 4)
                    : '0.0000';

                // Total COGS Allocated to Sale
                $totalCogs = bcmul($wacUnitCost, (string) $currentTx->quantity, 4);

                $runningQuantity -= (int) $currentTx->quantity;
                $runningTotalValue = bcsub($runningTotalValue, $totalCogs, 4);

                // Zero-Stock Depletion Reset Invariant
                if ($runningQuantity === 0) {
                    $runningTotalValue = '0.0000';
                }

                $currentTx->update([
                    'cogs_unit_cost' => $wacUnitCost,
                    'total_cogs' => $totalCogs,
                    'running_qty' => $runningQuantity,
                    'running_total_value' => $runningTotalValue,
                ]);
            }
        }

        // Sync Product top-level cached balance from latest active transaction
        $latestTransaction = StockTransaction::where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $product->update([
            'current_stock_qty' => $latestTransaction ? (int) $latestTransaction->running_qty : 0,
            'current_total_value' => $latestTransaction ? (string) $latestTransaction->running_total_value : '0.0000',
        ]);
    }
}
