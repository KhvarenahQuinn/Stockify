<?php

namespace App\Http\Requests;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeRequest extends FormRequest
{
    /**
     * The policy decides who may write master data. Repeating the role name
     * here is what kept managers read-only after the route guard had already
     * been opened to them.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', new Attribute) ?? false;
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
