<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Class InsufficientStockException
 *
 * Thrown when a stock transaction or historical recalculation cascade
 * would result in a negative running stock quantity for a product.
 */
class InsufficientStockException extends Exception
{
    /**
     * Render the exception into an HTTP JSON response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Unprocessable Entity',
            'message' => $this->getMessage() ?: 'Insufficient stock available for this transaction.',
        ], 422);
    }
}
