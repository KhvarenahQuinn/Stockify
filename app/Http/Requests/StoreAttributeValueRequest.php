<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->name === 'admin';
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.required' => __('Enter a value before adding it.'),
            'value.max' => __('The value may not be greater than 100 characters.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'value' => __('value'),
        ];
    }
}