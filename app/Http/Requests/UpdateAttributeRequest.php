<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeRequest extends FormRequest
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
        $attributeId = $this->route('attribute')?->id ?? $this->route('attribute');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('attributes', 'name')->ignore($attributeId)],
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
            'name.unique' => __('An attribute with this name already exists.'),
            'values.*.distinct' => __('Duplicate attribute values are not allowed.'),
        ];
    }
}