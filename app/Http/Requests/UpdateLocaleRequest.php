<?php

namespace App\Http\Requests;

use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocaleRequest extends FormRequest
{
    /**
     * Every signed-in account may change its own language.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', Rule::in(SetLocaleMiddleware::SUPPORTED)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'locale.required' => __('Choose a language.'),
            'locale.in' => __('That language is not supported.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'locale' => __('language'),
        ];
    }
}
