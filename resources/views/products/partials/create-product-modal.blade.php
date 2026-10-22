<x-modal-shell id="create-product-modal"
               title="{{ __('Add product') }}"
               subtitle="{{ __('Prices are in Indonesian rupiah; stock counts in the unit you choose.') }}"
               max-width="max-w-3xl"
               :form-action="Route::has('products.store') ? route('products.store') : null"
               :form-method="null"
               enctype="multipart/form-data">
    <x-slot:body>
        {{-- A file input posts multipart; without this Laravel silently drops
             the image on create. --}}
        <input type="hidden" name="_form" value="create-product-modal">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="name" label="{{ __('Product name') }}" required autocomplete="off"
                              placeholder="{{ __('e.g. Corrugated Carton Box Large') }}" />
            </div>

            <div>
                <x-form.input name="sku" label="{{ __('SKU') }}" prefix="PRD"
                              hint="{{ __('Leave blank and the system builds one from the category.') }}"
                              placeholder="{{ __('auto') }}" />
            </div>

            <x-form.select name="unit" label="{{ __('Unit of measure') }}" required
                          :options="['piece', 'box', 'roll', 'pack', 'set', 'kg', 'litre', 'metre']"
                          placeholder="{{ __('Select a unit') }}" />

            <x-form.select name="category_id" label="{{ __('Category') }}" required
                          :options="$categories"
                          placeholder="{{ __('Select a category') }}"
                          data-attribute-trigger="create-product" />

            <x-form.select name="supplier_id" label="{{ __('Supplier') }}" required
                          :options="$suppliers"
                          placeholder="{{ __('Select a supplier') }}" />

            <x-form.input name="buy_price" label="{{ __('Buy price') }}" type="number" required
                          inputmode="decimal" min="0" step="1" prefix="Rp"
                          placeholder="0" />

            <x-form.input name="sell_price" label="{{ __('Sell price') }}" type="number" required
                          inputmode="decimal" min="0" step="1" prefix="Rp"
                          placeholder="0" />

            <x-form.input name="min_stock" label="{{ __('Minimum stock') }}" type="number" required
                          inputmode="numeric" min="0" step="1"
                          hint="{{ __('Below this the product is flagged low stock.') }}" />

            <x-form.input name="current_stock" label="{{ __('Opening stock') }}" type="number"
                          inputmode="numeric" min="0" step="1"
                          hint="{{ __('Leave blank to start at zero.') }}" />

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="{{ __('Description') }}"
                                 placeholder="{{ __('What this product is and when to reorder it.') }}" />
            </div>

            <div class="sm:col-span-2">
                <x-form.file-upload name="image" label="{{ __('Product image') }}" />
            </div>

            <div class="sm:col-span-2">
                <x-products.attribute-selector :specs="$attributes" trigger="create-product" />
            </div>
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="create-product-modal"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>
        <button type="submit"
                class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            {{ __('Create product') }}
        </button>
    </x-slot:footer>
</x-modal-shell>