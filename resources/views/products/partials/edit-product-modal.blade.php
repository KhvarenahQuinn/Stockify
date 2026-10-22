@php $modalId = 'edit-product-'.$product->id; @endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Edit product') }}"
               subtitle="{{ $product->sku }}"
               max-width="max-w-3xl"
               :form-action="Route::has('products.update') ? route('products.update', $product) : null"
               :form-method="'PUT'"
               enctype="multipart/form-data">
    <x-slot:body>
        <input type="hidden" name="_form" value="{{ $modalId }}">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="name" label="{{ __('Product name') }}" required autocomplete="off"
                              :value="$product->name" />
            </div>

            <div>
                <x-form.input name="sku" label="{{ __('SKU') }}" prefix="PRD"
                              :value="$product->sku"
                              hint="{{ __('Clearing this restores the generated SKU.') }}" />
            </div>

            <x-form.select name="unit" label="{{ __('Unit of measure') }}" required
                          :options="['piece', 'box', 'roll', 'pack', 'set', 'kg', 'litre', 'metre']"
                          :value="$product->unit" placeholder="{{ __('Select a unit') }}" />

            <x-form.select name="category_id" label="{{ __('Category') }}" required
                          :options="$categories"
                          :value="$product->category_id" placeholder="{{ __('Select a category') }}"
                          data-attribute-trigger="{{ $modalId }}" />

            <x-form.select name="supplier_id" label="{{ __('Supplier') }}" required
                          :options="$suppliers"
                          :value="$product->supplier_id" placeholder="{{ __('Select a supplier') }}" />

            <x-form.input name="buy_price" label="{{ __('Buy price') }}" type="number" required
                          inputmode="decimal" min="0" step="1" prefix="Rp"
                          :value="$product->buy_price" />

            <x-form.input name="sell_price" label="{{ __('Sell price') }}" type="number" required
                          inputmode="decimal" min="0" step="1" prefix="Rp"
                          :value="$product->sell_price" />

            <x-form.input name="min_stock" label="{{ __('Minimum stock') }}" type="number" required
                          inputmode="numeric" min="0" step="1"
                          :value="$product->min_stock"
                          hint="{{ __('Below this the product is flagged low stock.') }}" />

            <x-form.input name="current_stock" label="{{ __('Current stock') }}" type="number" required
                          inputmode="numeric" min="0" step="1"
                          :value="$product->current_stock"
                          hint="{{ __('Adjusting this here bypasses the stock ledger.') }}" />

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="{{ __('Description') }}"
                                 :value="$product->description" />
            </div>

            <div class="sm:col-span-2">
                <x-form.file-upload name="image" label="{{ __('Product image') }}"
                                    :current-url="$product->image_url" />
            </div>

            <div class="sm:col-span-2">
                <x-products.attribute-selector
                    :specs="$attributes"
                    :selected="$product->attributeValues->pluck('id')"
                    :trigger="$modalId" />
            </div>
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="{{ $modalId }}"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>
        <button type="submit"
                class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            {{ __('Save changes') }}
        </button>
    </x-slot:footer>
</x-modal-shell>