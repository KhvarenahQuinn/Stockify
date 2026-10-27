<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * The policy decides who may write master data. Repeating the role name
     * here is what kept managers read-only after the route guard had already
     * been opened to them.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('categories', 'slug')],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('The category name is required.'),
            'name.string' => __('The category name must be a string.'),
            'name.max' => __('The category name may not be greater than 100 characters.'),
            'slug.unique' => __('A category with this slug already exists. Please choose a different name.'),
            'slug.max' => __('The slug may not be greater than 100 characters.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('category name'),
            'slug' => __('slug'),
            'description' => __('description'),
        ];
    }
}
