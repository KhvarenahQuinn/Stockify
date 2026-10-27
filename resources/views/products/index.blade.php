@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Contracts\Pagination\LengthAwarePaginator;

        /**
         * Product governance follows WORKFLOW.md: the manager and the
         * administrator share the catalogue, staff only reference it. Every
         * route reference is probed so a half-registered backend hides a
         * control instead of throwing.
         */
        $products = $products ?? collect();
        $categories = $categories ?? collect();

        // Ask the policy per ability rather than matching the role name here,
        // so a change to ProductPolicy reaches the screen automatically.
        $viewer = auth()->user();

        // The controller hands over a paginator, but a half-wired endpoint may
        // pass a plain collection, so totals are read through whichever it is.
        $paginator = $products instanceof LengthAwarePaginator ? $products : null;
        $total = $paginator?->total() ?? $products->count();

        $canCreate = ($viewer?->can('create', \App\Models\Product::class) ?? false) && Route::has('products.store');
        $canUpdate = ($viewer?->can('update', new \App\Models\Product) ?? false) && Route::has('products.update');
        $canDelete = ($viewer?->can('delete', new \App\Models\Product) ?? false) && Route::has('products.destroy');
        $canImport = ($viewer?->can('create', \App\Models\Product::class) ?? false) && Route::has('products.import');
        $canManage = $canCreate || $canUpdate || $canDelete;
        $showsActions = $canUpdate || $canDelete;
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Products') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __($total === 1 ? ':count product' : ':count products', ['count' => format_number($total)]) }}
                    <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                    {{ __('prices in IDR, stock counted in each product’s unit') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @unless ($canManage)
                    <x-master.read-only-note feature="products" />
                @endunless

                @if ($canImport)
                    <button type="button" data-modal-target="import-product-modal" data-modal-toggle="import-product-modal"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 10.5L12 15m0 0l-4.5-4.5M12 15V3"/>
                        </svg>
                        {{ __('Import') }}
                    </button>
                @endif

                @if ($canCreate)
                    <button type="button" data-modal-target="create-product-modal" data-modal-toggle="create-product-modal"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        {{ __('Add product') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ Route::has('products.index') ? route('products.index') : url()->current() }}"
                  class="flex flex-col gap-3 border-b border-gray-200 p-4 lg:flex-row lg:items-center dark:border-gray-700">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <label for="q" class="sr-only">{{ __('Search products') }}</label>
                    <input type="search" name="q" id="q" value="{{ request('q') }}" placeholder="{{ __('Search name or SKU') }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 pl-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                </div>

                <div class="sm:w-52">
                    <label for="category_id" class="sr-only">{{ __('Filter by category') }}</label>
                    <select name="category_id" id="category_id"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:w-48">
                    <label for="stock" class="sr-only">{{ __('Filter by stock status') }}</label>
                    <select name="stock" id="stock"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach (['all' => 'All stock levels', 'out' => 'Out of stock', 'low' => 'Low stock', 'safe' => 'Safe stock'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('stock', 'all') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        {{ __('Filter') }}
                    </button>
                    @if (request()->hasAny(['q', 'category_id', 'stock']))
                        <a href="{{ Route::has('products.index') ? route('products.index') : url()->current() }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <caption class="sr-only">{{ __('Product catalogue') }}</caption>
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        {{-- Column widths are declared here and repeated on the body cells: without
                             matching minimums a long product name or a big number squeezes
                             every other column and the badges start wrapping mid-word. --}}
                        <tr>
                            <th scope="col" class="w-1/4 min-w-[14rem] px-4 py-3">{{ __('Product') }}</th>
                            <th scope="col" class="min-w-[7rem] whitespace-nowrap px-4 py-3">{{ __('SKU') }}</th>
                            <th scope="col" class="hidden min-w-[9rem] px-4 py-3 md:table-cell">{{ __('Category') }}</th>
                            <th scope="col" class="hidden min-w-[9rem] px-4 py-3 lg:table-cell">{{ __('Supplier') }}</th>
                            <th scope="col" class="min-w-[6.5rem] whitespace-nowrap px-4 py-3 text-right">{{ __('Buy') }}</th>
                            <th scope="col" class="min-w-[6.5rem] whitespace-nowrap px-4 py-3 text-right">{{ __('Sell') }}</th>
                            <th scope="col" class="min-w-[8.5rem] whitespace-nowrap px-4 py-3 text-center">{{ __('Stock') }}</th>
                            @if ($showsActions)
                                <th scope="col" class="sticky right-0 z-10 bg-gray-50 px-4 py-3 text-right dark:bg-gray-700"><span class="sr-only">{{ __('Actions') }}</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php $imageUrl = $product->image_url; @endphp
                            {{-- `group` so the sticky cell can follow the row hover; otherwise it keeps its
                                 own white background and reads as a hole in a highlighted row. --}}
                            <tr class="group border-b border-gray-200 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40">
                                <th scope="row" class="w-1/4 min-w-[14rem] px-4 py-3 font-normal">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-600 dark:bg-gray-700">
                                            @if ($imageUrl)
                                                <img src="{{ $imageUrl }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                                                </svg>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            <button type="button"
                                                    data-modal-target="view-product-{{ $product->id }}"
                                                    data-modal-toggle="view-product-{{ $product->id }}"
                                                    class="block max-w-full truncate text-left font-medium text-gray-900 hover:text-primary-700 hover:underline focus:outline-none focus:ring-2 focus:ring-primary-400 dark:text-white">
                                                {{ $product->name }}
                                            </button>
                                            @if ($product->description)
                                                <span class="block max-w-full truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $product->description }}">{{ $product->description }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </th>
                                <td class="min-w-[7rem] whitespace-nowrap px-4 py-3">
                                    <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $product->sku }}</code>
                                </td>
                                <td class="hidden min-w-[9rem] px-4 py-3 md:table-cell">
                                    @if ($product->category)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $product->category->name }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">&mdash;</span>
                                    @endif
                                </td>
                                <td class="hidden min-w-[9rem] px-4 py-3 lg:table-cell">
                                    <span class="block max-w-[12rem] truncate" title="{{ $product->supplier?->name }}">{{ $product->supplier?->name ?? '—' }}</span>
                                </td>
                                <td class="min-w-[6.5rem] whitespace-nowrap px-4 py-3 text-right font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400">{{ 'Rp '.format_number((float) $product->buy_price, 0, ',', '.') }}</td>
                                <td class="min-w-[6.5rem] whitespace-nowrap px-4 py-3 text-right font-mono text-xs font-semibold tabular-nums text-gray-900 dark:text-white">{{ 'Rp '.format_number((float) $product->sell_price, 0, ',', '.') }}</td>
                                <td class="min-w-[8.5rem] whitespace-nowrap px-4 py-3 text-center">
                                    {{-- Count and badge side by side so the column reads as one
                                         scannable figure instead of two stacked fragments. --}}
                                    <span class="inline-flex items-center justify-center gap-2">
                                        <span class="font-mono text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                                            {{ format_number($product->current_stock) }}
                                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $product->unit }}</span>
                                        </span>
                                        <x-master.stock-badge :stock="$product->current_stock" :min-stock="$product->min_stock" />
                                    </span>
                                </td>
                                @if ($showsActions)
                                    <td class="sticky right-0 z-10 whitespace-nowrap border-l border-gray-200 bg-white px-4 py-3 text-right group-hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:group-hover:bg-gray-700/40">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            @if ($canUpdate)
                                                <button type="button"
                                                        data-modal-target="edit-product-{{ $product->id }}"
                                                        data-modal-toggle="edit-product-{{ $product->id }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                                    </svg>
                                                    {{ __('Edit') }}
                                                </button>
                                            @endif

                                            @if ($canDelete)
                                                <button type="button"
                                                        data-modal-target="delete-product-{{ $product->id }}"
                                                        data-modal-toggle="delete-product-{{ $product->id }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-200 dark:text-red-400 dark:hover:bg-gray-700 dark:focus:ring-red-800/40">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                    </svg>
                                                    {{ __('Delete') }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showsActions ? 8 : 7 }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    @if (request()->hasAny(['q', 'category_id', 'stock']) && request()->filled('q') === false && request('stock', 'all') === 'all' && ! request('category_id'))
                                        {{ __('No products yet.') }}
                                    @else
                                        {{ __('No products match these filters.') }}
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($paginator?->hasPages())
                <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>
    </div>

    @if ($canCreate)
        @include('products.partials.create-product-modal')
    @endif

    @foreach ($products as $product)
        @include('products.partials.view-product-modal', ['product' => $product])

        @if ($canUpdate)
            @include('products.partials.edit-product-modal', ['product' => $product])
        @endif

        @if ($canDelete)
            @include('products.partials.delete-product-modal', ['product' => $product])
        @endif
    @endforeach

    @if ($canImport)
        @include('products.partials.import-product-modal')
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Flowbite wires its data attributes on load only, so a validation
            // redirect has to re-init before reopening the form that owns the
            // errors. Each form carries a hidden `_form` key, so no guessing.
            if (typeof window.initFlowbite === 'function') {
                window.initFlowbite();
            }

            const form = @json(old('_form'));

            if (form) {
                document.querySelector(`[data-modal-toggle="${form}"]`)?.click();
            }
        });
    </script>
@endsection