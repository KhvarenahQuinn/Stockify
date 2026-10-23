<?php

namespace App\Http\Requests;

use App\Models\StockTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for a manual stock correction.
 *
 * A reason is mandatory: unlike a receipt, an adjustment has no supplier
 * paperwork behind it, so the explanation is the only evidence for why the
 * ledger moved.
 */
class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('adjust', StockTransaction::class) ?? false;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'string', Rule::in(['IN', 'OUT'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'adjustment_reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => __('Choose the product this adjustment is for.'),
            'product_id.exists' => __('That product is no longer in the catalogue.'),
            'type.required' => __('Choose whether the adjustment increases or decreases stock.'),
            'type.in' => __('The adjustment direction must be IN or OUT.'),
            'quantity.required' => __('Enter how many units the correction moves.'),
            'quantity.integer' => __('The adjustment quantity must be a whole number.'),
            'quantity.min' => __('The adjustment quantity must be at least 1.'),
            'adjustment_reason.required' => __('Explain why this stock correction is needed.'),
            'adjustment_reason.max' => __('The adjustment reason may not be longer than 500 characters.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => __('product'),
            'type' => __('adjustment direction'),
            'quantity' => __('quantity'),
            'adjustment_reason' => __('adjustment reason'),
        ];
    }
}
