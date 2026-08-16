<?php

namespace App\Http\Requests;

use App\Models\StockTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateTransactionRequest
 *
 * Form request validating updates to an existing transaction.
 * Ignores the current transaction ID when enforcing single daily active transaction.
 */
class UpdateTransactionRequest extends FormRequest
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
        $transactionId = $this->route('id');
        $transaction = StockTransaction::find($transactionId);
        $productId = $this->input('product_id', $transaction?->product_id);

        return [
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
            'transaction_date' => [
                'sometimes',
                'date_format:Y-m-d',
                Rule::unique('stock_transactions', 'transaction_date')
                    ->where(function ($query) use ($productId) {
                        return $query->where('product_id', $productId)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($transactionId),
            ],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'unit_cost' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
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
