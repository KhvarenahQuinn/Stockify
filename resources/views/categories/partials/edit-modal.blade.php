@php $modalId = 'edit-category-'.$category->id; @endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Edit category') }}"
               subtitle="{{ __('Renaming without touching the slug updates the slug to match.') }}"
               max-width="max-w-lg"
               :form-action="Route::has('categories.update') ? route('categories.update', $category) : null"
               :form-method="'PUT'">
    <x-slot:body>
        <input type="hidden" name="_form" value="{{ $modalId }}">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input name="name" label="{{ __('Category name') }}" required autocomplete="off"
                              :value="$category->name"
                              :data-slug-source="$modalId.'-slug'" />
            </div>

            <div class="sm:col-span-2">
                <x-form.input name="slug" :id="$modalId.'-slug'" label="{{ __('Slug') }}" required
                              :value="$category->slug"
                              prefix="/categories/" prefix-padding="pl-28"
                              hint="{{ __('Changing the slug breaks any handheld scanner bookmark pointing at the old path.') }}" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="description" label="{{ __('Description') }}"
                                 :value="$category->description" />
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