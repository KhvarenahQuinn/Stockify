@php $modalId = 'delete-attribute-'.$attribute->id; @endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Delete attribute') }}"
               max-width="max-w-md"
               :form-action="Route::has('attributes.destroy') ? route('attributes.destroy', $attribute) : null"
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
                    {{ __('Delete') }} <span class="font-medium text-gray-900 dark:text-white">{{ $attribute->name }}</span>?
                    {{ __('This cannot be undone.') }}
                </p>

                @if ($attribute->values->isNotEmpty())
                    <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                        {{ __($attribute->values->count() === 1 ? 'Its :count value will be deleted too, and any product specification built on them will be cleared.' : 'Its :count values will be deleted too, and any product specification built on them will be cleared.', ['count' => $attribute->values->count()]) }}
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
            {{ __('Delete attribute') }}
        </button>
    </x-slot:footer>
</x-modal-shell>