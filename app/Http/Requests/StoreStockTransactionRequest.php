<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for a goods receipt (IN) or goods issue (OUT) submission.
 */
class StoreStockTransactionRequest extends FormRequest
{
    /**
     * Submission is open to the roles that may move goods; approval is guarded
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
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $isInbound = strtoupper((string) $this->input('type')) === 'IN';

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'string', Rule::in(['IN', 'OUT'])],
            'quantity' => ['required', 'integer', 'min:1'],

            // The source of a receipt is a real supplier record, so it is
            // validated rather than trusted. On an issue the field is the other
            // way round: free text naming wherever the stock is going.
            'supplier_id' => [
                $isInbound ? 'required' : 'nullable',
                'nullable',
                'integer',
                'exists:suppliers,id',
            ],
            'destination' => [
                $isInbound ? 'nullable' : 'required',
                'nullable',
                'string',
                'max:150',
            ],

            'reference_note' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => __('Choose the product this movement is for.'),
            'product_id.exists' => __('That product is no longer in the catalogue.'),
            'type.required' => __('Choose whether goods are coming in or going out.'),
            'type.in' => __('The movement type must be IN or OUT.'),
            'quantity.required' => __('Enter how many units are moving.'),
            'quantity.integer' => __('The quantity must be a whole number.'),
            'quantity.min' => __('The quantity must be at least 1.'),
            'supplier_id.required' => __('Choose the supplier these goods came from.'),
            'supplier_id.exists' => __('That supplier is no longer on file.'),
            'destination.required' => __('Name where these goods are going.'),
            'destination.max' => __('The destination may not be longer than 150 characters.'),
            'reference_note.max' => __('The delivery note reference may not be longer than 120 characters.'),
            'notes.max' => __('The notes may not be longer than 1000 characters.'),
            'document.mimes' => __('The supporting document must be a PDF, JPEG, PNG or WebP file.'),
            'document.max' => __('The supporting document may not be larger than 5 MB.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => __('product'),
            'type' => __('movement type'),
            'quantity' => __('quantity'),
            'supplier_id' => __('supplier'),
            'destination' => __('destination'),
            'reference_note' => __('delivery note reference'),
            'document' => __('supporting document'),
        ];
    }
}