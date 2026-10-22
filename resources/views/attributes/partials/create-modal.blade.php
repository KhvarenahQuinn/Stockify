<x-modal-shell id="create-attribute-modal"
               title="{{ __('Add attribute') }}"
               subtitle="{{ __('Values are optional now and can be added later.') }}"
               max-width="max-w-lg"
               :form-action="Route::has('attributes.store') ? route('attributes.store') : null"
               :form-method="null">
    <x-slot:body>
        <input type="hidden" name="_form" value="create-attribute-modal">

        <div class="grid grid-cols-1 gap-4">
            <x-form.input name="name" label="{{ __('Attribute name') }}" required autocomplete="off"
                          placeholder="{{ __('e.g. Size, Colour, Brand') }}"
                          hint="{{ __('Use the label warehouse staff will recognise.') }}" />

            <x-master.tag-input label="{{ __('Values') }}"
                                :values="old('values', [])"
                                hint="{{ __('Press Enter or comma after each value. Leave empty if the list is not known yet.') }}" />
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="create-attribute-modal"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>
        <button type="submit"
                class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            {{ __('Create attribute') }}
        </button>
    </x-slot:footer>
</x-modal-shell>