<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    /**
     * Staff and above may record a physical count; verification is guarded
     * separately by the policy.
     */
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role?->name ?? '',
            ['warehouse_staff', 'warehouse_manager', 'admin'],
            true
        );
    }

    /**
     * Only the counted quantity is accepted: system_stock and difference are
     * derived server-side so they cannot be forged.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'physical_stock' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => __('Please choose a product to count.'),
            'product_id.exists' => __('The selected product does not exist.'),
            'physical_stock.required' => __('The physical count is required.'),
            'physical_stock.integer' => __('The physical count must be a whole number.'),
            'physical_stock.min' => __('The physical count may not be negative.'),
            'notes.max' => __('The notes may not be greater than 1000 characters.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => __('product'),
            'physical_stock' => __('physical stock'),
        ];
    }
}