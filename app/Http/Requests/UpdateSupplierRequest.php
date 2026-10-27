<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    /**
     * The policy decides who may write master data. Repeating the role name
     * here is what kept managers read-only after the route guard had already
     * been opened to them.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', new Supplier) ?? false;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id ?? $this->route('supplier');

        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($supplierId)],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:100'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[\+]?[\d\-\s\(\)]+$/'],
            'address' => ['sometimes', 'required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => __('A supplier with this code already exists.'),
            'email.email' => __('Please provide a valid email address.'),
            'phone.regex' => __('Please provide a valid phone number.'),
        ];
    }
}
