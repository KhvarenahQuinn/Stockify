<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('The category name is required.'),
            'slug.unique' => __('A category with this slug already exists. Please choose a different name.'),
            'slug.max' => __('The slug may not be greater than 100 characters.'),
        ];
    }
}