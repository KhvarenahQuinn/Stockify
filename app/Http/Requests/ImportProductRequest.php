<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ImportProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Bulk import follows the product create permission, so a role that
        // cannot add one product cannot add fifty through a spreadsheet.
        return $this->user()?->can('create', Product::class) ?? false;
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
