<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create the unified stock transactions ledger table.
     */
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->date('transaction_date');
            $table->enum('type', ['purchase', 'sale']);
            $table->integer('quantity');
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->decimal('unit_price', 14, 4)->nullable();
            $table->decimal('cogs_unit_cost', 14, 4)->nullable();
            $table->decimal('total_cogs', 14, 4)->nullable();
            $table->integer('running_qty')->default(0);
            $table->decimal('running_total_value', 14, 4)->default(0.0000);
            $table->timestamps();
            $table->softDeletes();

            // Composite Index for O(K) timeline range queries
            $table->index(['product_id', 'transaction_date'], 'idx_product_txdate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
