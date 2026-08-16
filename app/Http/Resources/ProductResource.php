<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProductResource
 *
 * API Resource transformer for Product model outputs.
 *
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'current_stock_quantity' => (int) $this->current_stock_qty,
            'current_total_value' => (string) number_format((float) $this->current_total_value, 4, '.', ''),
            'active_transactions_count' => $this->whenCounted('stockTransactions', $this->stock_transactions_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
