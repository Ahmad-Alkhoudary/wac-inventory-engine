<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class StockTransactionResource
 *
 * API Resource transformer for StockTransaction model outputs.
 *
 * @mixin \App\Models\StockTransaction
 */
class StockTransactionResource extends JsonResource
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
            'product_id' => (int) $this->product_id,
            'transaction_date' => $this->transaction_date->format('Y-m-d'),
            'type' => $this->type,
            'quantity' => (int) $this->quantity,
            'unit_cost' => $this->unit_cost !== null ? (string) number_format((float) $this->unit_cost, 4, '.', '') : null,
            'unit_price' => $this->unit_price !== null ? (string) number_format((float) $this->unit_price, 4, '.', '') : null,
            'cogs_unit_cost' => $this->cogs_unit_cost !== null ? (string) number_format((float) $this->cogs_unit_cost, 4, '.', '') : null,
            'total_cogs' => $this->total_cogs !== null ? (string) number_format((float) $this->total_cogs, 4, '.', '') : null,
            'running_qty' => (int) $this->running_qty,
            'running_total_value' => (string) number_format((float) $this->running_total_value, 4, '.', ''),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
