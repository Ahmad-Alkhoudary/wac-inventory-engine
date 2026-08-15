<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Product
 *
 * Represents an inventory product catalog item.
 *
 * @property int $id Unique product identifier
 * @property string $sku Stock Keeping Unit (Unique)
 * @property string $name Human-readable product name
 * @property int $current_stock_qty Cached running inventory total quantity
 * @property string $current_total_value Cached running total inventory valuation (DECIMAL 14,4)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockTransaction> $stockTransactions
 */
class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'current_stock_qty',
        'current_total_value',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_stock_qty' => 'integer',
            'current_total_value' => 'string',
        ];
    }

    /**
     * Get all stock transaction ledger entries for this product.
     *
     * @return HasMany<StockTransaction, $this>
     */
    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }
}
