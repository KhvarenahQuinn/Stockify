<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Http\Requests\StoreStockOpnameSheetRequest;
use App\Services\StockOpnameSheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The multi-line audit sheet: one session, many counted products.
 *
 * The single-product flow still lives on StockOpnameController, which the
 * dashboard reads from. This controller owns the counting UI.
 */
class StockOpnameSheetController extends Controller
{
    public const INDEX_PER_PAGE = 15;

    /**
     * History of completed audits, filterable by date, auditor and outcome.
     */
    public function index(Request $request, StockOpnameSheetService $service): View
    {
        $filters = array_filter($request->only(['status', 'auditor_id', 'from', 'to', 'variance_status']));

        return view('stock-opnames.index', [
            'sheets' => $service->listSheets(self::INDEX_PER_PAGE, $filters),
            'filters' => $filters,
            'auditors' => $service->auditors(),
        ]);
    }

    /**
     * The counting sheet.
     */
    public function create(StockOpnameSheetService $service): View
    {
        return view('stock-opnames.create', [
            'products' => $service->productsForCounting(),
            'auditors' => $service->auditors(),
        ]);
    }

    /**
     * Persist a submitted sheet.
     */
    public function store(StoreStockOpnameSheetRequest $request, StockOpnameSheetService $service): RedirectResponse
    {
        try {
            $sheet = $service->storeSheet($request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $count = $sheet->totalItems();
        $summary = __(':count products', ['count' => $count]);

        return redirect()->route('stock-opnames.show', $sheet)
            ->with('success', __('Count :reference submitted: :summary counted, stock reconciled.', ['reference' => $sheet->reference, 'summary' => $summary]));
    }

    /**
     * A single completed audit, line by line.
     */
    public function show(int $sheet, StockOpnameSheetService $service): View
    {
        return view('stock-opnames.show', [
            'sheet' => $service->findSheet($sheet),
        ]);
    }
}