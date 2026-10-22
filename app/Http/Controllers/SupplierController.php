<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public const INDEX_PER_PAGE = 15;

    public function __construct(private readonly SupplierService $service) {}

    public function index(Request $request): View
    {
        return view('suppliers.index', [
            'suppliers' => $this->service->listSuppliers(self::INDEX_PER_PAGE, $request->string('q')->trim()->value()),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = $this->service->createSupplier(
            $request->safe()->only(['code', 'name', 'email', 'phone', 'address'])
        );

        return redirect()->route('suppliers.index')
            ->with('success', __('Supplier :name created.', ['name' => $supplier->name]));
    }

    public function update(UpdateSupplierRequest $request, int $supplier): RedirectResponse
    {
        $this->service->updateSupplier(
            $supplier,
            $request->safe()->only(['code', 'name', 'email', 'phone', 'address'])
        );

        return redirect()->route('suppliers.index')
            ->with('success', __('Supplier updated.'));
    }

    public function destroy(int $supplier): RedirectResponse
    {
        $this->service->deleteSupplier($supplier);

        return redirect()->route('suppliers.index')
            ->with('success', __('Supplier deleted.'));
    }
}