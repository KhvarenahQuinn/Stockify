<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockOpnameSheetController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
/*
|--------------------------------------------------------------------------
| Entry Point
|--------------------------------------------------------------------------
|
| Stockify has no public content — the only screen a signed-out visitor can
| reach is the login form. The root hands off by auth state rather than
| rendering a placeholder, so opening the DDEV URL always lands somewhere
| useful. The practice views stay mounted under /practice for testing.
|
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('index-practice');

Route::name('practice.')->group(function () {
    Route::name('first')->get('practice/1', function () {
        return view('pages.practice.1');
    });
    Route::name('second')->get('practice/2', function () {
        return view('pages.practice.2');
    });
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('logout', [LogoutController::class, 'destroy'])->name('logout')->middleware('auth');

// Dashboard
Route::get('dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');

// Stock opnames: one audit sheet counts many products in a single session.
// Staff, managers and admins may run a count and submit it; submitting is what
// reconciles the official stock balance, so the confirm dialog gates the write.
Route::middleware(['auth', 'role:warehouse_staff,warehouse_manager,admin'])
    ->prefix('stock-opnames')
    ->name('stock-opnames.')
    ->group(function () {
        Route::get('/', [StockOpnameSheetController::class, 'index'])->name('index');
        Route::get('create', [StockOpnameSheetController::class, 'create'])->name('create');
        Route::post('/', [StockOpnameSheetController::class, 'store'])->name('store');
        Route::get('{sheet}', [StockOpnameSheetController::class, 'show'])->name('show');
    });

// Reports: readable by warehouse roles, exportable by managers and admins.
Route::middleware(['auth', 'role:warehouse_staff,warehouse_manager,admin'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('transactions', [ReportController::class, 'transactions'])->name('transactions');
        Route::get('activity', [ReportController::class, 'activity'])->name('activity');
    });

Route::middleware(['auth', 'role:warehouse_manager,admin'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('stock/export', [ReportController::class, 'exportStock'])->name('stock.export');
        Route::get('transactions/export', [ReportController::class, 'exportTransactions'])->name('transactions.export');
        Route::get('activity/export', [ReportController::class, 'exportActivity'])->name('activity.export');
    });

// Application settings are administrator-only.
Route::middleware(['auth', 'role:admin'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/', [SettingController::class, 'update'])->name('update');
    });

// Products: the catalogue is readable by every warehouse role, but per
// WORKFLOW.md only the manager owns it — admin is deliberately not granted
// product write access, and staff are read-only.
Route::middleware(['auth', 'role:warehouse_staff,warehouse_manager,admin'])
    ->prefix('products')
    ->name('products.')
    ->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:warehouse_manager'])
    ->prefix('products')
    ->name('products.')
    ->group(function () {
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::put('{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('{product}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('import', [ProductController::class, 'import'])->name('import');
        Route::get('import-template', [ProductController::class, 'template'])->name('template');
    });

// Master data is readable by warehouse roles so managers can pick categories,
// attributes and suppliers while registering products. Only administrators may
// change the records themselves; the views hide the controls to match.
Route::middleware(['auth', 'role:warehouse_manager,admin'])
    ->prefix('categories')
    ->name('categories.')
    ->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('categories')
    ->name('categories.')
    ->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:warehouse_manager,admin'])
    ->prefix('suppliers')
    ->name('suppliers.')
    ->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('suppliers')
    ->name('suppliers.')
    ->group(function () {
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::put('{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::delete('{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:warehouse_manager,admin'])
    ->prefix('attributes')
    ->name('attributes.')
    ->group(function () {
        Route::get('/', [AttributeController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('attributes')
    ->name('attributes.')
    ->group(function () {
        Route::post('/', [AttributeController::class, 'store'])->name('store');
        Route::put('{attribute}', [AttributeController::class, 'update'])->name('update');
        Route::delete('{attribute}', [AttributeController::class, 'destroy'])->name('destroy');

        // Values hang off an attribute but are addressed on their own, so they
        // get their own prefix to avoid swallowing the {attribute} route above.
        Route::post('{attribute}/values', [AttributeController::class, 'storeValue'])->name('values.store');
        Route::delete('values/{value}', [AttributeController::class, 'destroyValue'])->name('values.destroy');
    });

// Protected routes
Route::middleware(['auth', 'role:admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
});

// Stock transactions: any movement role can submit and browse the ledger.
//
// Every literal segment is registered before the {transaction} wildcard.
// Laravel matches in declaration order, so a `queue` route added underneath
// `GET stock-transactions/{transaction}` would never be reached — the wildcard
// would swallow it and 404 on a lookup for a transaction id.
Route::middleware(['auth', 'role:warehouse_staff,warehouse_manager,admin'])
    ->prefix('stock-transactions')
    ->name('stock-transactions.')
    ->group(function () {
        Route::get('/', [StockTransactionController::class, 'index'])->name('index');
        Route::get('create', [StockTransactionController::class, 'create'])->name('create');
        Route::post('/', [StockTransactionController::class, 'store'])->name('store');

        // The floor queue: staff count what actually arrived or left, so every
        // movement role can open it and post a physical count.
        Route::get('queue', [StockTransactionController::class, 'staffQueue'])->name('queue');
        Route::patch('{transaction}/inspect', [StockTransactionController::class, 'inspect'])->name('inspect');
    });

// Approval actions are restricted to managers and admins.
Route::middleware(['auth', 'role:warehouse_manager,admin'])
    ->prefix('stock-transactions')
    ->name('stock-transactions.')
    ->group(function () {
        Route::get('approvals', [StockTransactionController::class, 'approvals'])->name('approvals');
        Route::patch('{transaction}/approve', [StockTransactionController::class, 'approve'])->name('approve');
        Route::patch('{transaction}/reject', [StockTransactionController::class, 'reject'])->name('reject');
        Route::patch('{transaction}/complete', [StockTransactionController::class, 'complete'])->name('complete');
    });

Route::middleware(['auth', 'role:warehouse_staff,warehouse_manager,admin'])
    ->prefix('stock-transactions')
    ->name('stock-transactions.')
    ->group(function () {
        Route::get('{transaction}/document', [StockTransactionController::class, 'document'])->name('document');
        Route::get('{transaction}', [StockTransactionController::class, 'show'])->name('show');
    });
