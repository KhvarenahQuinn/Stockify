<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->name === 'admin';
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