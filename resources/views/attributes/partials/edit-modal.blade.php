@php $modalId = 'edit-attribute-'.$attribute->id; @endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Edit attribute') }}"
               max-width="max-w-lg"
               :form-action="Route::has('attributes.update') ? route('attributes.update', $attribute) : null"
               :form-method="'PUT'">
    <x-slot:body>
        <input type="hidden" name="_form" value="{{ $modalId }}">

        <div class="grid grid-cols-1 gap-4">
            <x-form.input name="name" label="{{ __('Attribute name') }}" required autocomplete="off"
                          :value="$attribute->name" />

            <x-master.tag-input label="{{ __('Values') }}"
                                :values="old('values', $attribute->values->pluck('value')->all())"
                                hint="{{ __('Removing a value here deletes it and unlinks it from every product using it.') }}" />
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