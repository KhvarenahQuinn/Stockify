@php
    $modalId = 'delete-value-'.$attributeValue->id;
    $inUse = ($attributeValue->products_count ?? 0) > 0;
@endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Remove value') }}"
               max-width="max-w-md"
               :form-action="Route::has('attributes.values.destroy') ? route('attributes.values.destroy', $attributeValue) : null"
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
                    {{ __('Remove :value from :attribute?', ['value' => $attributeValue->value, 'attribute' => $attribute->name]) }}
                    {{ __('This cannot be undone.') }}
                </p>

                @if ($inUse)
                    <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-100">
                        {{ __($attributeValue->products_count === 1 ? ':count product uses this value. It loses the attribute when the value goes.' : ':count products use this value. They lose the attribute when the value goes.', ['count' => format_number($attributeValue->products_count)]) }}
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
            {{ __('Remove value') }}
        </button>
    </x-slot:footer>
</x-modal-shell>
