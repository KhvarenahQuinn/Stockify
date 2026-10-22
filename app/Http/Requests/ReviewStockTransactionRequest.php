<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A manager's decision on a pending movement.
 *
 * The review note is required on both branches. On approval it is the record of
 * what was checked; on rejection it is the only thing the submitter gets to go
 * on, and "rejected" with no reason sends them back to the desk to guess.
 */
class ReviewStockTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role?->name ?? '',
            ['warehouse_manager', 'admin'],
            true
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notes.required' => __('Write a review note before deciding. It becomes part of the audit trail.'),
            'notes.min' => __('Say a little more — at least 5 characters.'),
            'notes.max' => __('The review note may not be longer than 1000 characters.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'notes' => __('review note'),
        ];
    }
}