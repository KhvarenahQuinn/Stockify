<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Only an administrator may change application-wide settings.
     */
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
            'app_name' => ['required', 'string', 'max:100'],
            'default_min_stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'app_logo' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:1024',
            ],
            'remove_logo' => ['sometimes', 'boolean'],
            // The recipient and the threshold are only meaningful when the
            // alerts are on, so they are required exactly then and not before.
            'email_alerts_enabled' => ['sometimes', 'boolean'],
            'email_alerts_recipient' => [
                'nullable',
                'email',
                'max:255',
                'required_if:email_alerts_enabled,1',
            ],
            'email_alerts_min_stock' => [
                'nullable',
                'integer',
                'min:0',
                'max:100000',
                'required_if:email_alerts_enabled,1',
            ],
            'locale' => ['nullable', Rule::in(['en', 'id'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'app_name.required' => __('The application name is required.'),
            'app_name.max' => __('The application name may not be greater than 100 characters.'),
            'default_min_stock.required' => __('The default minimum stock is required.'),
            'default_min_stock.integer' => __('The default minimum stock must be a whole number.'),
            'default_min_stock.min' => __('The default minimum stock may not be negative.'),
            'default_min_stock.max' => __('The default minimum stock is unrealistically high.'),
            'app_logo.image' => __('The logo must be a valid image file.'),
            'app_logo.mimes' => __('The logo must be a JPEG, PNG or WebP file.'),
            'app_logo.max' => __('The logo may not be greater than 1 MB.'),
            'email_alerts_recipient.required_if' => __('Add an email address to send the alerts to.'),
            'email_alerts_recipient.email' => __('That does not look like an email address.'),
            'email_alerts_min_stock.required_if' => __('Choose the stock level that should raise an alert.'),
            'email_alerts_min_stock.integer' => __('The alert level must be a whole number.'),
            'email_alerts_min_stock.min' => __('The alert level may not be negative.'),
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
            'app_name' => __('application name'),
            'default_min_stock' => __('default minimum stock'),
            'app_logo' => __('logo'),
            'email_alerts_recipient' => __('email address'),
            'email_alerts_min_stock' => __('alert level'),
            'locale' => __('language'),
        ];
    }
}