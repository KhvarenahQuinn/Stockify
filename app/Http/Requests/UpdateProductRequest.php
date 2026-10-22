<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // WORKFLOW.md places product management outside the admin remit: the
        // warehouse manager owns the catalogue, everyone else reads it.
        return $this->user()?->role?->name === 'warehouse_manager';
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('product');

        return [
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['sometimes', 'required', 'integer', 'exists:suppliers,id'],
            'buy_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'sell_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'min_stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'current_stock' => ['sometimes', 'integer', 'min:0'],
            'unit' => ['sometimes', 'required', 'string', 'max:20'],
            'image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:2048',
            ],
            'attribute_value_ids' => ['sometimes', 'array'],
            'attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.unique' => __('Another product is already using this SKU.'),
            'sku.max' => __('The SKU may not be greater than 50 characters.'),
            'name.required' => __('The product name is required.'),
            'name.max' => __('The product name may not be greater than 150 characters.'),
            'category_id.exists' => __('The selected category does not exist.'),
            'supplier_id.exists' => __('The selected supplier does not exist.'),
            'buy_price.numeric' => __('The buy price must be a number.'),
            'buy_price.min' => __('The buy price may not be negative.'),
            'sell_price.numeric' => __('The sell price must be a number.'),
            'sell_price.min' => __('The sell price may not be negative.'),
            'min_stock.integer' => __('The minimum stock must be a whole number.'),
            'min_stock.min' => __('The minimum stock may not be negative.'),
            'current_stock.integer' => __('The current stock must be a whole number.'),
            'current_stock.min' => __('The current stock may not be negative.'),
            'unit.max' => __('The unit may not be greater than 20 characters.'),
            'image.image' => __('The product image must be a valid image file.'),
            'image.mimes' => __('The product image must be a JPEG, PNG or WebP file.'),
            'image.max' => __('The product image may not be greater than 2 MB.'),
            'attribute_value_ids.*.exists' => __('One of the selected attribute values does not exist.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('product name'),
            'buy_price' => __('buy price'),
            'sell_price' => __('sell price'),
            'min_stock' => __('minimum stock'),
            'current_stock' => __('current stock'),
            'unit' => __('unit'),
        ];
    }
}