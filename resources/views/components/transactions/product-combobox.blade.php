@props([
    'products',
    'name' => 'product_id',
    'label' => __('Product'),
    'required' => true,
    'selectedId' => null,
    'hint' => null,
])

@php
    use App\Support\StockLevel;

    $fieldId = 'field-'.$name;
    $listboxId = $fieldId.'-listbox';
    $selected = $products->firstWhere('id', (int) old($name, $selectedId));
    $message = $errors->first($name);
@endphp

{{--
    A text box over a hidden input rather than a <select>.

    The catalogue outgrows a dropdown long before it outgrows a warehouse, and a
    native select forces scrolling through every SKU to reach the one you want.
    The hidden input keeps the server contract identical to the select it
    replaces, so validation, old-input repopulation and the controller all carry
    on working unchanged.
--}}
<div data-product-combobox>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
        {{ $label }}
        @if ($required)
            <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="relative">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
        </svg>

        <input
            type="text"
            id="{{ $fieldId }}"
            value="{{ $selected ? $selected->name : '' }}"
            placeholder="{{ __('Search by name or SKU') }}"
            autocomplete="off"
            role="combobox"
            aria-expanded="false"
            aria-controls="{{ $listboxId }}"
            aria-autocomplete="list"
            aria-describedby="{{ $message ? 'error-'.$fieldId : ($hint ? 'hint-'.$fieldId : 'product-help-'.$fieldId) }}"
            @if ($required) aria-required="true" @endif
            @if ($message) aria-invalid="true" @endif
            data-product-search
            @class([
                'block w-full rounded-lg border bg-gray-50 p-2.5 pl-9 pr-9 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:bg-white focus:outline-none focus:ring-1 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:bg-gray-800',
                'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500' => $message,
                'border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600' => ! $message,
            ])
        >

        {{-- The field the server actually reads. --}}
        <input type="hidden" name="{{ $name }}" value="{{ $selected?->id }}" data-product-value>

        <button type="button"
                data-product-clear
                class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:hover:bg-gray-600 dark:hover:text-white"
                aria-label="{{ __('Clear the selected product') }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <ul id="{{ $listboxId }}"
            role="listbox"
            aria-label="{{ __('Products') }}"
            data-product-listbox
            class="absolute left-0 right-0 z-30 mt-1 hidden max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-600 dark:bg-gray-700">
            @foreach ($products as $product)
                <li role="none" data-product-row>
                    <button type="button"
                            role="option"
                            aria-selected="false"
                            id="{{ $listboxId }}-option-{{ $product->id }}"
                            data-product-option
                            data-id="{{ $product->id }}"
                            data-name="{{ $product->name }}"
                            data-sku="{{ $product->sku }}"
                            data-stock="{{ (int) $product->current_stock }}"
                            data-unit="{{ $product->unit }}"
                            data-min-stock="{{ (int) $product->min_stock }}"
                            class="block w-full px-3 py-2 text-left hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:hover:bg-gray-600 dark:focus:bg-gray-600">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-white" data-option-name>{{ $product->name }}</span>
                            <span class="shrink-0 font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                <span data-option-stock>{{ format_number($product->current_stock) }}</span>
                                <span data-option-unit>{{ $product->unit }}</span>
                            </span>
                        </span>
                        <span class="mt-0.5 flex items-center gap-2">
                            <code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-[11px] text-gray-600 dark:bg-gray-600 dark:text-gray-300">{{ $product->sku }}</code>
                            @if ($product->current_stock <= 0)
                                <span class="text-[11px] font-semibold text-red-700 dark:text-red-400">{{ __('Out of stock') }}</span>
                            @elseif ($product->current_stock <= $product->min_stock)
                                <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-400">{{ __('At reorder point') }}</span>
                            @endif
                        </span>
                    </button>
                </li>
            @endforeach

            <li role="none" data-product-empty class="hidden px-3 py-3 text-sm text-gray-500 dark:text-gray-400">
                {{ __('No product matches that. Try the SKU.') }}
            </li>
        </ul>
    </div>

    {{-- What actually got chosen, restated. The text box can be edited after a
         pick, and this is the only place that tells you the edit did not stick.

         The badge starts blank and is painted by transaction-ui.js from the same
         StockLevel map the server-side badge uses. Rendering it here with a
         placeholder value would put "Out of stock" next to "210 box on hand" the
         moment a product was picked. --}}
    <div data-product-chip
         class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-600 dark:bg-gray-700/50 {{ $selected ? '' : 'hidden' }}"
         data-stock-tones="{{ json_encode(StockLevel::tones()) }}">
        <span class="min-w-0">
            <span class="block truncate text-sm font-medium text-gray-900 dark:text-white" data-product-chip-name>
                {{ $selected?->name }}
            </span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">
                <span class="font-mono" data-product-chip-sku>{{ $selected?->sku }}</span>
                <span data-product-chip-stock>
                    @if ($selected)
                        · {{ format_number($selected->current_stock) }} {{ $selected->unit }} {{ __('on hand') }}
                    @endif
                </span>
            </span>
        </span>

        <span class="shrink-0" data-product-chip-level>
            @if ($selected)
                <x-master.stock-badge :stock="$selected->current_stock" :min-stock="$selected->min_stock" />
            @endif
        </span>
    </div>

    @if ($message)
        <p id="error-{{ $fieldId }}" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @else
        <p id="{{ $hint ? 'hint-' : 'product-help-' }}{{ $fieldId }}" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            {{ $hint ?? __('Start typing to search the catalogue. Stock updates as you pick.') }}
        </p>
    @endif

    <p class="sr-only" role="status" aria-live="polite" data-product-count></p>
</div>