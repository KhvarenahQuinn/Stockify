@php
    $modalId = 'delete-product-'.$product->id;
    $movements = $product->stockTransactions()->count();
@endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Delete product') }}"
               max-width="max-w-md"
               :form-action="Route::has('products.destroy') ? route('products.destroy', $product) : null"
               :form-method="'DELETE'">
    <x-slot:body>
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
            </span>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('Delete') }} <span class="font-medium text-gray-900 dark:text-white">{{ $product->name }}</span>
                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">({{ $product->sku }})</span>?
                    {{ __('This cannot be undone.') }}
                </p>

                @if ($movements > 0)
                    <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-100">
                        {{ __($movements === 1 ? ':count stock movement references this product. Its history will be removed with it.' : ':count stock movements reference this product. Their history will be removed with it.', ['count' => format_number($movements)]) }}
                    </p>
                @endif
            </div>
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="{{ $modalId }}"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>
        <button type="submit"
                class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800">
            {{ __('Delete product') }}
        </button>
    </x-slot:footer>
</x-modal-shell>