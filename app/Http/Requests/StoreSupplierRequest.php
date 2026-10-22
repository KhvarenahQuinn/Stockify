<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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
        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('suppliers', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[\+]?[\d\-\s\(\)]+$/'],
            'address' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => __('A supplier with this code already exists.'),
            'code.max' => __('The supplier code may not be greater than 50 characters.'),
            'name.required' => __('The supplier name is required.'),
            'name.string' => __('The supplier name must be a string.'),
            'name.max' => __('The supplier name may not be greater than 100 characters.'),
            'email.required' => __('The supplier email is required.'),
            'email.email' => __('Please provide a valid email address.'),
            'email.max' => __('The email may not be greater than 100 characters.'),
            'phone.required' => __('The supplier phone number is required.'),
            'phone.string' => __('The phone number must be a string.'),
            'phone.max' => __('The phone number may not be greater than 20 characters.'),
            'phone.regex' => __('Please provide a valid phone number.'),
            'address.required' => __('The supplier address is required.'),
            'address.string' => __('The address must be a string.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('supplier name'),
            'email' => __('email address'),
            'phone' => __('phone number'),
            'address' => __('address'),
        ];
    }
}