<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\StockTransaction
 *
 * Represents an individual stock movement event (purchase or sale) in the unified inventory ledger.
 *
 * @property int $id Unique transaction identifier
 * @property int $product_id Foreign key referencing the product
 * @property \Illuminate\Support\Carbon $transaction_date Date of the transaction
 * @property string $type Event type ('purchase' or 'sale')
 * @property int $quantity Quantity of items moved
 * @property string|null $unit_cost Unit purchase cost (DECIMAL 14,4) for purchases
 * @property string|null $unit_price Unit selling price (DECIMAL 14,4) for sales
 * @property string|null $cogs_unit_cost Point-in-time Weighted Average Cost snapshot allocated to sale
 * @property string|null $total_cogs Total Cost of Goods Sold allocated to sale (quantity * cogs_unit_cost)
 * @property int $running_qty Stock on hand post-event
 * @property string $running_total_value Total asset valuation post-event (DECIMAL 14,4)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at Soft delete timestamp
 * @property-read \App\Models\Product $product
 */
class StockTransaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'transaction_date',
        'type',
        'quantity',
        'unit_cost',
        'unit_price',
        'cogs_unit_cost',
        'total_cogs',
        'running_qty',
        'running_total_value',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_date' => 'date:Y-m-d',
            'quantity' => 'integer',
            'running_qty' => 'integer',
            'unit_cost' => 'string',
            'unit_price' => 'string',
            'cogs_unit_cost' => 'string',
            'total_cogs' => 'string',
            'running_total_value' => 'string',
        ];
    }

    /**
     * Get the product associated with this stock transaction.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
