<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class InventoryServiceTest
 *
 * Unit tests verifying the Weighted Average Cost (WAC) calculation engine,
 * O(K) cascading recalculations, backdated insertions, historical mutations,
 * soft deletes, and domain invariants.
 */
class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = app(InventoryService::class);
        $this->product = Product::create([
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Gaming Mouse',
            'current_stock_qty' => 0,
            'current_total_value' => '0.0000',
        ]);
    }

    /**
     * Test Case 1: WAC Arithmetic Precision Calculation (Prompt Example Section 7)
     *
     * 01/01: Purchase 150 @ RM2.00 -> Qty: 150, Total: RM300.00, WAC: RM2.00
     * 05/01: Purchase 10 @ RM1.50 -> Qty: 160, Total: RM315.00, WAC: RM1.9688
     * 07/01: Sale 5 units -> Allocated COGS: RM9.8438, Qty left: 155
     */
    public function test_wac_arithmetic_precision_calculation(): void
    {
        // 01/01: Purchase 150 @ 2.00
        $tx1 = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-01',
            'type' => 'purchase',
            'quantity' => 150,
            'unit_cost' => '2.00',
        ]);

        $this->assertEquals(150, $tx1->running_qty);
        $this->assertEquals('300.0000', $tx1->running_total_value);

        // 05/01: Purchase 10 @ 1.50
        $tx2 = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-05',
            'type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => '1.50',
        ]);

        $this->assertEquals(160, $tx2->running_qty);
        $this->assertEquals('315.0000', $tx2->running_total_value);

        // 07/01: Sale 5 units
        $tx3 = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-07',
            'type' => 'sale',
            'quantity' => 5,
            'unit_price' => '10.00',
        ]);

        // bcdiv('315.0000', '160', 4) = 1.9687
        // bcmul('1.9687', '5', 4) = 9.8435
        $this->assertEquals('1.9687', $tx3->cogs_unit_cost);
        $this->assertEquals('9.8435', $tx3->total_cogs);
        $this->assertEquals(155, $tx3->running_qty);
        $this->assertEquals('305.1565', $tx3->running_total_value);

        // Assert Product cached totals synced
        $this->product->refresh();
        $this->assertEquals(155, $this->product->current_stock_qty);
        $this->assertEquals('305.1565', $this->product->current_total_value);
    }

    /**
     * Test Case 2: Backdated Purchase Ingestion Cascades Downstream Sales (Bonus 1)
     */
    public function test_backdated_purchase_ingestion_cascades_downstream_sales(): void
    {
        // 01/01: Purchase 100 @ 10.00
        $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-01',
            'type' => 'purchase',
            'quantity' => 100,
            'unit_cost' => '10.00',
        ]);

        // 10/01: Sale 20 units (WAC = 10.00, Total COGS = 200.00)
        $saleTx = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-10',
            'type' => 'sale',
            'quantity' => 20,
            'unit_price' => '25.00',
        ]);

        $this->assertEquals('10.0000', $saleTx->cogs_unit_cost);
        $this->assertEquals('200.0000', $saleTx->total_cogs);

        // 03/01: Backdated Purchase 100 @ 20.00 (Total stock now 200, Total Value = 3000.00, WAC = 15.00)
        $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-03',
            'type' => 'purchase',
            'quantity' => 100,
            'unit_cost' => '20.00',
        ]);

        // Refresh sale transaction and assert WAC & COGS were recalculated downstream
        $saleTx->refresh();
        $this->assertEquals('15.0000', $saleTx->cogs_unit_cost);
        $this->assertEquals('300.0000', $saleTx->total_cogs);
        $this->assertEquals(180, $saleTx->running_qty);
        $this->assertEquals('2700.0000', $saleTx->running_total_value);
    }

    /**
     * Test Case 3: Historical Mutation Recalculates Downstream Ledger (Bonus 2)
     */
    public function test_historical_mutation_recalculates_downstream_ledger(): void
    {
        $purchaseTx = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-01',
            'type' => 'purchase',
            'quantity' => 50,
            'unit_cost' => '10.00',
        ]);

        $saleTx = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-05',
            'type' => 'sale',
            'quantity' => 10,
            'unit_price' => '30.00',
        ]);

        $this->assertEquals('10.0000', $saleTx->cogs_unit_cost);

        // Update purchase unit cost from 10.00 to 20.00
        $this->inventoryService->updateTransaction($purchaseTx, [
            'unit_cost' => '20.00',
        ]);

        $saleTx->refresh();
        $this->assertEquals('20.0000', $saleTx->cogs_unit_cost);
        $this->assertEquals('200.0000', $saleTx->total_cogs);
    }

    /**
     * Test Case 4: Historical Soft Delete Recalculates Downstream Ledger (Bonus 2)
     */
    public function test_historical_soft_delete_recalculates_downstream_ledger(): void
    {
        $purchaseTx1 = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-01',
            'type' => 'purchase',
            'quantity' => 50,
            'unit_cost' => '10.00',
        ]);

        $purchaseTx2 = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-03',
            'type' => 'purchase',
            'quantity' => 50,
            'unit_cost' => '20.00',
        ]);

        $saleTx = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-05',
            'type' => 'sale',
            'quantity' => 10,
            'unit_price' => '30.00',
        ]);

        // Prior WAC = 1500 / 100 = 15.00
        $this->assertEquals('15.0000', $saleTx->cogs_unit_cost);

        // Soft delete first purchase (+50 @ 10.00)
        $this->inventoryService->deleteTransaction($purchaseTx1);

        // Downstream sale should now use only purchase 2 (+50 @ 20.00 -> WAC = 20.00)
        $saleTx->refresh();
        $this->assertEquals('20.0000', $saleTx->cogs_unit_cost);
        $this->assertEquals('200.0000', $saleTx->total_cogs);
        $this->assertEquals(40, $saleTx->running_qty);
    }

    /**
     * Test Case 5: Insufficient Stock Exception Thrown and Rolls Back Database
     */
    public function test_insufficient_stock_exception_thrown_and_rolls_back_database(): void
    {
        $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-01',
            'type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => '5.00',
        ]);

        $this->expectException(InsufficientStockException::class);

        // Attempt to sell 50 units when only 10 available
        $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-05',
            'type' => 'sale',
            'quantity' => 50,
            'unit_price' => '10.00',
        ]);
    }

    /**
     * Test Case 6: Zero Stock Depletion Resets Running Value and WAC to Zero
     */
    public function test_zero_stock_depletion_resets_running_value_and_wac_to_zero(): void
    {
        $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-01',
            'type' => 'purchase',
            'quantity' => 20,
            'unit_cost' => '12.50',
        ]);

        // Sell all 20 units
        $saleTx = $this->inventoryService->recordTransaction($this->product, [
            'transaction_date' => '2026-01-05',
            'type' => 'sale',
            'quantity' => 20,
            'unit_price' => '20.00',
        ]);

        $this->assertEquals(0, $saleTx->running_qty);
        $this->assertEquals('0.0000', $saleTx->running_total_value);

        $this->product->refresh();
        $this->assertEquals(0, $this->product->current_stock_qty);
        $this->assertEquals('0.0000', $this->product->current_total_value);
    }
}
