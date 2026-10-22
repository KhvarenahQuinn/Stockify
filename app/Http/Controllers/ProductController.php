<?php

namespace App\Http\Controllers;

use App\Exports\ProductTemplateExport;
use App\Http\Requests\ImportProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Imports\ProductImport;
use App\Services\AttributeService;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public const INDEX_PER_PAGE = 15;

    public function __construct(
        private readonly ProductService $service,
        private readonly CategoryService $categories,
        private readonly SupplierService $suppliers,
        private readonly AttributeService $attributes,
    ) {}

    public function index(Request $request): View
    {
        $stock = $request->string('stock')->toString();

        return view('products.index', [
            'products' => $this->service->listProducts(
                self::INDEX_PER_PAGE,
                $request->string('q')->trim()->value(),
                $request->integer('category_id') ?: null,
                $stock === 'all' ? null : $stock,
            ),
            // Lookup lists for the filter bar and the create/edit pickers.
            'categories' => $this->categories->allCategories(),
            'suppliers' => $this->suppliers->allSuppliers(),
            'attributes' => $this->attributes->allAttributesWithValues(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->service->createProduct(
            $request->safe()->except(['image', 'attribute_value_ids']),
            $request->validated('attribute_value_ids', []),
            $request->file('image'),
        );

        return redirect()->route('products.index')
            ->with('success', __('Product :sku created.', ['sku' => $product->sku]));
    }

    public function update(UpdateProductRequest $request, int $product): RedirectResponse
    {
        $attributes = $request->safe()->except([
            'image',
            'attribute_value_ids',
            'remove_image',
        ]);

        // An explicit removal beats leaving the file input untouched; the file
        // input is disabled while the box is ticked so the two cannot disagree.
        if ($request->boolean('remove_image')) {
            $attributes['image'] = null;
        }

        $this->service->updateProduct(
            $product,
            $attributes,
            $request->has('attribute_value_ids') ? $request->validated('attribute_value_ids', []) : null,
            $request->file('image'),
        );

        return redirect()->route('products.index')
            ->with('success', __('Product updated.'));
    }

    public function destroy(int $product): RedirectResponse
    {
        $this->service->deleteProduct($product);

        return redirect()->route('products.index')
            ->with('success', __('Product deleted.'));
    }

    /**
     * Import a spreadsheet. Failed rows are skipped by ProductImport rather
     * than aborting the run, so the summary has to name both numbers.
     */
    public function import(ImportProductRequest $request): RedirectResponse
    {
        $import = new ProductImport();

        Excel::import($import, $request->file('file'));

        $message = $import->imported === 0
            ? __('No products were imported. Check the column headings and the values in each row.')
            : __($import->imported === 1 ? 'Imported :count product.' : 'Imported :count products.', ['count' => $import->imported]);

        if ($import->skipped > 0) {
            $message .= ' '.__($import->skipped === 1 ? ':count row was skipped.' : ':count rows were skipped.', ['count' => $import->skipped]);
        }

        return redirect()->route('products.index')
            ->with($import->imported > 0 ? 'success' : 'error', $message);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new ProductTemplateExport(), 'product-import-template.xlsx');
    }
}