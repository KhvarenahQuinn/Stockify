<?php

namespace App\Http\Requests;

use App\Services\StockOpnameSheetService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockOpnameSheetRequest extends FormRequest
{
    /**
     * Staff, managers and admins may run an audit sheet. Posting the stock
     * adjustment is a separate, manager-only step guarded by the policy.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role?->name ?? '', StockOpnameSheetService::COUNTER_ROLES, true);
    }

    /**
     * Only the counted quantity and the note are accepted. system_stock and
     * variance are derived server-side so they cannot be forged.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                // distinct catches a double-counted product before the database
                // unique index has to reject the whole insert.
                'distinct:ignore_case',
                Rule::exists('products', 'id'),
            ],
            'items.*.physical_stock' => ['required', 'integer', 'min:0'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
            'auditor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('status', 'active')->whereIn('role_id', function ($roleQuery) {
                        $roleQuery->select('id')
                            ->from('roles')
                            ->whereIn('name', StockOpnameSheetService::COUNTER_ROLES);
                    });
                }),
            ],
            'audit_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Staff may only file a count under their own name. Managers and admins can
     * record a count on behalf of a counter, which is the documented workflow.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user === null
                || in_array($user->role?->name ?? '', StockOpnameSheetService::SUPERVISOR_ROLES, true)
            ) {
                return;
            }

            if ((int) $this->input('auditor_id') !== (int) $user->id) {
                $validator->errors()->add(
                    'auditor_id',
                    __('You can only record a count under your own name.')
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => __('Add at least one product to the count sheet.'),
            'items.min' => __('Add at least one product to the count sheet.'),
            'items.*.product_id.required' => __('Every count line needs a product.'),
            'items.*.product_id.distinct' => __('The same product appears twice. Count it once.'),
            'items.*.product_id.exists' => __('One of the counted products no longer exists.'),
            'items.*.physical_stock.required' => __('Enter what you physically counted.'),
            'items.*.physical_stock.integer' => __('A count must be a whole number.'),
            'items.*.physical_stock.min' => __('A count cannot be negative.'),
            'items.*.reason.max' => __('Keep each reason under 255 characters.'),
            'auditor_id.required' => __('Name the auditor running this count.'),
            'audit_date.date' => __('Enter a valid audit date.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'items' => __('count sheet'),
            'auditor_id' => __('auditor'),
            'audit_date' => __('audit date'),
            'notes' => __('notes'),
        ];
    }
}