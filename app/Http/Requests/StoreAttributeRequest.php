<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->name === 'admin';
    }

    /**
     * @return array<string, array<int, string|Rule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('attributes', 'name')],
            'values' => ['sometimes', 'array'],
            'values.*' => ['required', 'string', 'max:100', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('The attribute name is required.'),
            'name.string' => __('The attribute name must be a string.'),
            'name.max' => __('The attribute name may not be greater than 50 characters.'),
            'name.unique' => __('An attribute with this name already exists.'),
            'values.array' => __('Attribute values must be provided as an array.'),
            'values.*.required' => __('Each attribute value is required and cannot be empty.'),
            'values.*.string' => __('Each attribute value must be a string.'),
            'values.*.max' => __('Each attribute value may not be greater than 100 characters.'),
            'values.*.distinct' => __('Duplicate attribute values are not allowed.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('attribute name'),
            'values' => __('attribute values'),
        ];
    }
}