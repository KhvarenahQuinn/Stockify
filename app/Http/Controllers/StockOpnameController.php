<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public const INDEX_PER_PAGE = 15;

    /**
     * Display the stock count history with optional filters.
     */
    public function index(Request $request, StockOpnameService $service): View
    {
        $filters = $request->only(['status', 'product_id', 'from', 'to']);

        $onlyDiscrepancies = $request->boolean('only_discrepancies');
        if ($onlyDiscrepancies) {
            $filters['only_discrepancies'] = true;
        }

        return view('stock-opnames.index', [
            'opnames' => $service->listOpnames(self::INDEX_PER_PAGE, array_filter($filters)),
            'filters' => $filters,
            'statuses' => [
                StockOpnameService::STATUS_DRAFT,
                StockOpnameService::STATUS_ADJUSTED,
            ],
            'onlyDiscrepancies' => $onlyDiscrepancies,
        ]);
    }

    /**
     * Show the form for staff to enter a physical count.
     */
    public function create(StockOpnameService $service): View
    {
        return view('stock-opnames.create', [
            'products' => $service->productsForCounting(),
        ]);
    }

    /**
     * Store a new DRAFT count.
     */
    public function store(StoreStockOpnameRequest $request, StockOpnameService $service): RedirectResponse
    {
        $opname = $service->createOpname($request->validated());

        $message = $opname->difference === 0
            ? __('Count :number recorded with no discrepancy.', ['number' => $opname->opname_number])
            : __('Count :number recorded with a difference of :difference.', ['number' => $opname->opname_number, 'difference' => $opname->difference]);

        return redirect()->route('stock-opnames.show', $opname)
            ->with('success', $message);
    }

    /**
     * Display a single count.
     */
    public function show(int $id, StockOpnameService $service): View
    {
        return view('stock-opnames.show', [
            'opname' => $service->findOpname($id),
        ]);
    }

    /**
     * Manager verification: post the adjustment to the product's stock.
     */
    public function verify(int $id, Request $request, StockOpnameService $service): RedirectResponse
    {
        abort_unless(
            $request->user()?->can('verify', StockOpname::class) ?? false,
            403,
            __('You are not allowed to verify stock counts.')
        );

        try {
            $opname = $service->adjust($id, $request->input('notes'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('stock-opnames.show', $opname)
            ->with('success', __('Count :number verified and stock adjusted.', ['number' => $opname->opname_number]));
    }
}