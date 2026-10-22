<x-modal-shell id="create-category-modal"
               title="{{ __('Add category') }}"
               subtitle="{{ __('Name and slug both feed the inventory API.') }}"
               max-width="max-w-lg"
               :form-action="Route::has('categories.store') ? route('categories.store') : null"
               :form-method="null">
    <x-slot:body>
        <input type="hidden" name="_form" value="create-category-modal">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="name" label="{{ __('Category name') }}" required autocomplete="off"
                              data-slug-source="create-category-slug"
                              placeholder="{{ __('e.g. Packaging materials') }}" />
            </div>

            <div class="sm:col-span-2">
                <x-form.input name="slug" id="create-category-slug" label="{{ __('Slug') }}" required
                              prefix="/categories/" prefix-padding="pl-28"
                              hint="{{ __('Leave blank and the system derives one from the name.') }}"
                              placeholder="{{ __('packaging-materials') }}" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="{{ __('Description') }}"
                                 placeholder="{{ __('What belongs in this category.') }}"
                                 hint="{{ __('Shown in pickers and on category reports.') }}" />
            </div>
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="create-category-modal"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>
        <button type="submit"
                class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            {{ __('Create category') }}
        </button>
    </x-slot:footer>
</x-modal-shell>