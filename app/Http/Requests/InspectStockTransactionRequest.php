<?php

namespace App\Http\Requests;

use App\Models\StockTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The physical count a warehouse operator records against a submitted request.
 */
class InspectStockTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role?->name ?? '',
            ['warehouse_staff', 'warehouse_manager', 'admin'],
            true
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'inspected_quantity' => ['required', 'integer', 'min:0'],

            // A note explaining a mismatch is the whole point of flagging one.
            // A matching count needs none — an empty box beside "48 counted" is
            // not information, it is friction.
            'inspection_note' => [
                Rule::requiredIf($this->countDoesNotMatch()),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'inspected_quantity.required' => __('Enter the quantity you counted on the floor.'),
            'inspected_quantity.integer' => __('The counted quantity must be a whole number.'),
            'inspected_quantity.min' => __('A counted quantity cannot be negative. Enter 0 if nothing arrived.'),
            'inspection_note.required' => __('Say what you found. A count that does not match needs a note to go with it.'),
            'inspection_note.max' => __('The inspection note may not be longer than 1000 characters.'),
        ];
    }

    /**
     * The requested quantity lives on the transaction, not on this request, so
     * the comparison has to go and get it.
     *
     * The route parameter arrives as a plain id string rather than a bound
     * model — the controller signature takes an int, matching the rest of this
     * controller — so it cannot be dereferenced directly.
     */
    protected function countDoesNotMatch(): bool
    {
        $transaction = StockTransaction::query()->find($this->route('transaction'));

        if ($transaction === null || ! $this->filled('inspected_quantity')) {
            return false;
        }

        return (int) $this->input('inspected_quantity') !== (int) $transaction->quantity;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inspected_quantity' => __('counted quantity'),
            'inspection_note' => __('inspection note'),
        ];
    }
}