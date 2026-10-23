@extends('layouts.app')

@section('content')
@php
    use App\Models\StockOpnameSheet;

    /**
     * The catalogue and any rows a rejected submission came back with are
     * handed to the script as JSON. Re-adding them goes through the same
     * addRow() path a live row takes, so a validation failure repaints the
     * sheet through the one row template rather than a second copy of the
     * markup that could drift from it.
     */
    $catalogue = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'current_stock' => (int) $product->current_stock,
        'unit' => $product->unit,
        'category_id' => $product->category_id,
        'category' => $product->category?->name,
    ])->values();

    $categories = $products
        ->map(fn ($product) => $product->category)
        ->filter()
        ->unique('id')
        ->sortBy('name')
        ->values();

    $restore = collect(old('items', []))
        ->map(fn ($line) => [
            'product_id' => $line['product_id'] ?? null,
            'physical_stock' => $line['physical_stock'] ?? '',
            'reason' => $line['reason'] ?? '',
        ])
        ->filter(fn ($line) => $line['product_id'])
        ->values();
@endphp

<div class="p-4" data-opname-sheet
     data-opname-confirm="confirm-opname-modal"
     data-opname-catalogue="{{ json_encode($catalogue) }}"
     data-opname-restore="{{ json_encode($restore) }}">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Stock Opname') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Count what is on the shelf. The difference against system stock is worked out as you type, then a manager verifies the sheet before stock changes.') }}
            </p>
        </div>

        @if (Route::has('stock-opnames.index'))
            <a href="{{ route('stock-opnames.index') }}"
               class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-400 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                {{ __('Count history') }}
            </a>
        @endif
    </div>

    <div data-opname-alert role="alert" aria-live="assertive"
         class="mb-4 hidden rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-100"></div>

    @error('items')
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
            {{ $message }}
        </div>
    @enderror

    <form method="POST" action="{{ Route::has('stock-opnames.store') ? route('stock-opnames.store') : '#' }}"
          data-opname-form>
        @csrf

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Product picker ------------------------------------------------ --}}
            <section class="xl:col-span-1"
                     aria-labelledby="picker-heading">
                <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                        <h2 id="picker-heading" class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('Add products') }}
                        </h2>

                        <div class="mt-3 space-y-3">
                            <div>
                                <label for="opname-search"
                                       class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Search') }}
                                </label>
                                <input type="search" id="opname-search" data-opname-search
                                       placeholder="{{ __('Name or SKU') }}"
                                       autocomplete="off"
                                       class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                            </div>

                            <div>
                                <label for="opname-category"
                                       class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Category') }}
                                </label>
                                <select id="opname-category" data-opname-category
                                        class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">{{ __('All categories') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <p class="px-4 pt-3 text-xs text-gray-500 dark:text-gray-400" data-opname-results-count></p>

                    <div class="max-h-[26rem] overflow-y-auto" data-opname-results>
                        <p class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Loading products…') }}
                        </p>
                    </div>
                </div>
            </section>

            {{-- Count sheet ----------------------------------------------------- --}}
            <section class="xl:col-span-2" aria-labelledby="sheet-heading">
                <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                        <h2 id="sheet-heading" class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('Count sheet') }}
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">
                                {{ __('Products on this count, with system stock, the physical count and the difference between them') }}
                            </caption>
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th scope="col" class="min-w-[14rem] px-4 py-3">{{ __('Product') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('System') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Physical') }}</th>
                                    <th scope="col" class="min-w-[12rem] px-4 py-3">{{ __('Reason') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Difference') }}</th>
                                    <th scope="col" class="px-4 py-3">
                                        <span class="sr-only">{{ __('Remove') }}</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody data-opname-rows class="hidden divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                        </table>
                    </div>

                    <p data-opname-empty
                       class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Nothing on the sheet yet. Pick products from the list to start counting.') }}
                    </p>
                </div>

                {{-- Live summary ---------------------------------------------------- --}}
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <x-opnames.summary-metric metric="items" label="{{ __('Items audited') }}" :value="0" />
                    <x-opnames.summary-metric metric="matched" label="{{ __('Matched') }}" tone="matched" :value="0" />
                    <x-opnames.summary-metric metric="deficit" label="{{ __('Deficit') }}" tone="deficit" :value="0" />
                    <x-opnames.summary-metric metric="surplus" label="{{ __('Surplus') }}" tone="surplus" :value="0" />
                    <x-opnames.summary-metric metric="net" label="{{ __('Net variance') }}" :value="0"
                                               class="col-span-2 sm:col-span-1" />
                </div>

                {{-- Sheet metadata --------------------------------------------------- --}}
                <div class="mt-4 grid gap-4 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-2 dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <label for="auditor_id"
                               class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('Auditor') }}
                        </label>
                        <select name="auditor_id" id="auditor_id" required
                                class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white {{ $errors->has('auditor_id') ? 'border-red-500' : '' }}">
                            <option value="">{{ __('Select the auditor') }}</option>
                            @foreach ($auditors as $auditor)
                                <option value="{{ $auditor->id }}" @selected((string) old('auditor_id', auth()->id()) === (string) $auditor->id)>
                                    {{ $auditor->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('auditor_id')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="audit_date"
                               class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('Audit date') }}
                        </label>
                        <input type="date" name="audit_date" id="audit_date"
                               value="{{ old('audit_date', now()->toDateString()) }}"
                               class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white {{ $errors->has('audit_date') ? 'border-red-500' : '' }}">
                        @error('audit_date')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes"
                               class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('Notes') }} <span class="font-normal text-gray-500 dark:text-gray-400">{{ __('(optional)') }}</span>
                        </label>
                        <textarea name="notes" id="notes" rows="2" maxlength="1000"
                                  placeholder="{{ __('Anything that explains the count as a whole') }}"
                                  class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit"
                            class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400">
                        {{ __('Review and submit') }}
                    </button>

                    @if (Route::has('stock-opnames.index'))
                        <a href="{{ route('stock-opnames.index') }}"
                           class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                            {{ __('Cancel') }}
                        </a>
                    @endif
                </div>
            </section>
        </div>
    </form>

    {{-- Templates: Blade owns every class, the script only fills them in ---- --}}
    <template data-opname-row-template>
        <tr data-opname-row class="border-b border-gray-100 dark:border-gray-700">
            <td class="px-4 py-3">
                <input type="hidden" name="items[0][product_id]" value="">
                <span class="block font-medium text-gray-900 dark:text-white" data-row-product-name></span>
                <span class="block font-mono text-xs text-gray-500 dark:text-gray-400" data-row-product-sku></span>
            </td>

            <td class="px-4 py-3 text-right">
                <span class="tabular-nums text-gray-700 dark:text-gray-300" data-row-system-stock></span>
                <span class="sr-only">{{ __('system stock, read-only') }}</span>
            </td>

            <td class="px-4 py-3 text-right">
                <input type="number" name="items[0][physical_stock]" min="0" step="1" inputmode="numeric"
                       data-physical-input
                       aria-label="{{ __('Physical count') }}"
                       placeholder="0"
                       class="w-24 rounded-lg border border-gray-300 bg-white p-2 text-right text-sm text-gray-900 tabular-nums focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </td>

            <td class="px-4 py-3">
                <input type="text" name="items[0][reason]" maxlength="255"
                       aria-label="{{ __('Reason for the difference') }}"
                       placeholder="{{ __('Damage, shrinkage…') }}"
                       class="w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
            </td>

            <td class="px-4 py-3 text-right" data-variance-slot></td>

            <td class="px-4 py-3 text-right">
                <button type="button" data-remove-row
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 dark:hover:bg-red-950 dark:hover:text-red-300">
                    <span class="sr-only">{{ __('Remove this line from the count sheet') }}</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </td>
        </tr>
    </template>

    @foreach (['matched' => 0, 'surplus' => 3, 'deficit' => -4] as $kind => $sample)
        <template data-opname-badge-template="{{ $kind }}">
            <x-opnames.variance-badge :variance="$sample" />
        </template>
    @endforeach

    @include('stock-opnames.partials.confirm-opname-modal')
</div>
@endsection