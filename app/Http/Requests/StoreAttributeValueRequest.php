<?php

namespace App\Http\Requests;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeValueRequest extends FormRequest
{
    /**
     * A value is part of its attribute, so editing values follows the same
     * ability as editing the attribute itself.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', new Attribute) ?? false;
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
