<x-modal-shell id="import-product-modal"
               title="{{ __('Import products') }}"
               subtitle="{{ __('Rows are matched to existing categories and suppliers by name.') }}"
               max-width="max-w-2xl"
               :form-action="Route::has('products.import') ? route('products.import') : null"
               :form-method="null"
               enctype="multipart/form-data">
    <x-slot:body>
        <input type="hidden" name="_form" value="import-product-modal">

        <div data-dropzone="import"
             class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition-colors dark:border-gray-600 dark:bg-gray-700/30">
            <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>

            <p data-dropzone-idle class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                {{ __('Drag a spreadsheet here, or') }}
                <label for="import-file" class="cursor-pointer font-medium text-primary-700 underline hover:no-underline dark:text-primary-400">
                    {{ __('browse for a file') }}
                </label>.
            </p>

            <p data-dropzone-filename class="mt-2 hidden text-sm font-medium text-gray-900 dark:text-white"></p>

            <input type="file" name="file" id="import-file"
                   data-dropzone-input="import"
                   accept=".csv,.xlsx,.xls"
                   required
                   class="mt-4 block w-full max-w-sm cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-900 file:mr-3 file:cursor-pointer file:border-0 file:bg-gray-200 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-300 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:file:bg-gray-600 dark:file:text-gray-200">
        </div>

        @error('file')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <p data-dropzone-message class="mt-2 hidden text-sm text-red-600 dark:text-red-400" role="alert"></p>

        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/30">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Column headings') }}</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                {{ __('The first row must contain exactly these headings.') }}
                <code class="rounded bg-gray-200 px-1 py-0.5 font-mono text-xs text-gray-800 dark:bg-gray-600 dark:text-gray-200">category</code>
                {{ __('and') }}
                <code class="rounded bg-gray-200 px-1 py-0.5 font-mono text-xs text-gray-800 dark:bg-gray-600 dark:text-gray-200">supplier</code>
                {{ __('accept a name or, for suppliers, a supplier code.') }}
            </p>

            <ul class="mt-2 flex flex-wrap gap-1.5 font-mono text-xs">
                @foreach (['sku', 'name', 'description', 'category', 'supplier', 'buy_price', 'sell_price', 'min_stock', 'current_stock', 'unit'] as $heading)
                    <li class="rounded bg-gray-200 px-1.5 py-0.5 text-gray-800 dark:bg-gray-600 dark:text-gray-200">{{ $heading }}</li>
                @endforeach
            </ul>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Rows that fail validation are skipped, not aborted. The summary after import reports how many landed.') }}
            </p>
        </div>
    </x-slot:body>

    <x-slot:footer>
        @if (Route::has('products.template'))
            <a href="{{ route('products.template') }}"
               class="mr-auto inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 10.5V21m0 0l3-3m-3 3l-3-3M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM13.5 6A2.25 2.25 0 0115.75 3.75H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6z"/>
                </svg>
                {{ __('Download sample template') }}
            </a>
        @endif

        <button type="button" data-modal-hide="import-product-modal"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>
        <button type="submit"
                class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            {{ __('Import products') }}
        </button>
    </x-slot:footer>
</x-modal-shell>