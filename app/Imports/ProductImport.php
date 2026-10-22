<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Supplier;
use App\Services\ProductService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class ProductImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public int $imported = 0;

    public int $skipped = 0;

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                $this->createProductFromRow($row);

                $this->imported++;
            } catch (\Throwable $e) {
                $this->skipped++;
            }
        }
    }

    /**
     * Required by SkipsOnFailure; row-level failures are already counted in
     * collection(), so nothing further is needed here.
     */
    public function onFailure(Failure ...$failures)
    {
        $this->skipped += count($failures);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['sometimes', 'nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['required', 'string'],
            'supplier' => ['required', 'string'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'current_stock' => ['sometimes', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'The product name is required.',
            'buy_price.numeric' => 'The buy price must be a number.',
            'buy_price.min' => 'The buy price may not be negative.',
            'sell_price.numeric' => 'The sell price must be a number.',
            'sell_price.min' => 'The sell price may not be negative.',
            'min_stock.integer' => 'The minimum stock must be a whole number.',
            'min_stock.min' => 'The minimum stock may not be negative.',
        ];
    }

    /**
     * Resolve a category/supplier by name or code and delegate to the service,
     * which owns SKU generation and persistence.
     */
    protected function createProductFromRow(Collection $row): void
    {
        app(ProductService::class)->createProduct(
            [
                'sku' => $this->cell($row, 'sku'),
                'name' => $this->cell($row, 'name'),
                'description' => $this->cell($row, 'description'),
                'category_id' => $this->resolveCategoryId((string) $this->cell($row, 'category')),
                'supplier_id' => $this->resolveSupplierId((string) $this->cell($row, 'supplier')),
                'buy_price' => $this->cell($row, 'buy_price'),
                'sell_price' => $this->cell($row, 'sell_price'),
                'min_stock' => $this->cell($row, 'min_stock'),
                'current_stock' => $this->cell($row, 'current_stock') ?? 0,
                'unit' => $this->cell($row, 'unit'),
            ],
        );
    }

    protected function resolveCategoryId(string $name): int
    {
        $category = Category::query()
            ->where('slug', Str::slug($name))
            ->orWhere('name', $name)
            ->firstOrFail();

        return (int) $category->id;
    }

    protected function resolveSupplierId(string $reference): int
    {
        $supplier = Supplier::query()
            ->where('code', $reference)
            ->orWhere('name', $reference)
            ->firstOrFail();

        return (int) $supplier->id;
    }

    protected function cell(Collection $row, string $key): mixed
    {
        $value = $row->get($key);

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}