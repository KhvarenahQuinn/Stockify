<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // WORKFLOW.md gives product management to the manager and the admin;
        // the policy is the single place that decides it.
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'current_stock' => ['sometimes', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
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
            'sku.unique' => __('A product with this SKU already exists.'),
            'sku.max' => __('The SKU may not be greater than 50 characters.'),
            'name.required' => __('The product name is required.'),
            'name.max' => __('The product name may not be greater than 150 characters.'),
            'category_id.required' => __('Please choose a category for this product.'),
            'category_id.exists' => __('The selected category does not exist.'),
            'supplier_id.required' => __('Please choose a supplier for this product.'),
            'supplier_id.exists' => __('The selected supplier does not exist.'),
            'buy_price.required' => __('The buy price is required.'),
            'buy_price.numeric' => __('The buy price must be a number.'),
            'buy_price.min' => __('The buy price may not be negative.'),
            'sell_price.required' => __('The sell price is required.'),
            'sell_price.numeric' => __('The sell price must be a number.'),
            'sell_price.min' => __('The sell price may not be negative.'),
            'min_stock.required' => __('The minimum stock is required.'),
            'min_stock.integer' => __('The minimum stock must be a whole number.'),
            'min_stock.min' => __('The minimum stock may not be negative.'),
            'current_stock.integer' => __('The current stock must be a whole number.'),
            'current_stock.min' => __('The current stock may not be negative.'),
            'unit.required' => __('The unit of measure is required.'),
            'unit.max' => __('The unit may not be greater than 20 characters.'),
            'image.image' => __('The product image must be a valid image file.'),
            'image.mimes' => __('The product image must be a JPEG, PNG or WebP file.'),
            'image.max' => __('The product image may not be greater than 2 MB.'),
            'attribute_value_ids.array' => __('Attribute values must be provided as an array.'),
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
