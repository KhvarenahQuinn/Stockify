<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
