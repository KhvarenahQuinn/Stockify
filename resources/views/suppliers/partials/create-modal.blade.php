<x-modal-shell id="create-supplier-modal"
               title="{{ __('Add supplier') }}"
               subtitle="{{ __('Codes are assigned automatically in the SUP-0000 series.') }}"
               max-width="max-w-2xl"
               :form-action="Route::has('suppliers.store') ? route('suppliers.store') : null"
               :form-method="null">
    <x-slot:body>
        <input type="hidden" name="_form" value="create-supplier-modal">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-form.input name="code" label="{{ __('Supplier code') }}" prefix="SUP"
                              hint="{{ __('Blank takes the next free code.') }}"
                              placeholder="0007" />
            </div>

            <div>
                <x-form.input name="name" label="{{ __('Supplier name') }}" required autocomplete="organization"
                              placeholder="{{ __('PT Sinar Kemasan') }}" />
            </div>

            <div>
                <x-form.input name="email" type="email" label="{{ __('Email') }}" required
                              autocomplete="email" inputmode="email"
                              placeholder="orders@example.com" />
            </div>

            <div>
                <x-form.input name="phone" type="tel" label="{{ __('Phone') }}" required
                              autocomplete="tel" inputmode="tel"
                              placeholder="+62 21 555 0134" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="address" label="{{ __('Address') }}" required
                                 placeholder="{{ __('Street, number, district, city, postal code.') }}"
                                 hint="{{ __('Printed on purchase orders and delivery notes.') }}" />
            </div>
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="create-supplier-modal"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>
        <button type="submit"
                class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            {{ __('Create supplier') }}
        </button>
    </x-slot:footer>
</x-modal-shell>