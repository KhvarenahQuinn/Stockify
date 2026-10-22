<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Bulk import matches the product CRUD permission: the warehouse
        // manager owns the catalogue, staff and admin do not.
        return $this->user()?->role?->name === 'warehouse_manager';
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Choose a spreadsheet to import.'),
            'file.mimes' => __('The import file must be a CSV or Excel workbook.'),
            'file.max' => __('The import file may not be greater than 5 MB.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => __('import file'),
        ];
    }
}