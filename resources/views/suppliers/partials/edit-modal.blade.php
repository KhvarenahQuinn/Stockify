@php $modalId = 'edit-supplier-'.$supplier->id; @endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Edit supplier') }}"
               :max-width="'max-w-2xl'"
               :form-action="Route::has('suppliers.update') ? route('suppliers.update', $supplier) : null"
               :form-method="'PUT'">
    <x-slot:body>
        <input type="hidden" name="_form" value="{{ $modalId }}">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-form.input name="code" label="{{ __('Supplier code') }}" prefix="SUP" required
                              :value="$supplier->code"
                              hint="{{ __('Renaming the code breaks references on historic purchase orders.') }}" />
            </div>

            <div>
                <x-form.input name="name" label="{{ __('Supplier name') }}" required autocomplete="organization"
                              :value="$supplier->name" />
            </div>

            <div>
                <x-form.input name="email" type="email" label="{{ __('Email') }}" required
                              autocomplete="email" inputmode="email"
                              :value="$supplier->email" />
            </div>

            <div>
                <x-form.input name="phone" type="tel" label="{{ __('Phone') }}" required
                              autocomplete="tel" inputmode="tel"
                              :value="$supplier->phone" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="address" label="{{ __('Address') }}" required
                                 :value="$supplier->address" />
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