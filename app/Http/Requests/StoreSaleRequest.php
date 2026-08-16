<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreSaleRequest
 *
 * Form request validating incoming sale transaction payloads.
 * Enforces single active transaction per date per product (soft-delete aware).
 */
class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->input('product_id');

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'transaction_date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('stock_transactions', 'transaction_date')
                    ->where(function ($query) use ($productId) {
                        return $query->where('product_id', $productId)
                            ->whereNull('deleted_at');
                    }),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    /**
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_date.unique' => 'Only one active transaction is allowed per product per date.',
        ];
    }
}
