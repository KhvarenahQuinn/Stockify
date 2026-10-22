@props([
    'name' => 'values[]',
    'label',
    'values' => [],
    'hint' => null,
    'placeholder' => __('Type a value, then press Enter'),
])

@php
    $fieldId = 'tag-'.preg_replace('/[^a-z0-9]+/i', '-', $name);
    $seeded = array_values(array_filter(array_map('trim', (array) $values), fn ($v) => $v !== ''));
    // `values.*` carries per-item failures as a list of strings, `values`
    // carries the array-level one. Either way only the first is worth showing.
    $itemErrors = $errors->get('values.*');
    $arrayError = $errors->first('values');
    $message = ($itemErrors[0] ?? null) ?: $arrayError;
    $hasError = $message !== null;
@endphp

<div data-tag-input>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</label>

    <div data-tag-chips
         class="flex flex-wrap gap-2 rounded-lg border bg-gray-50 p-2 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:bg-gray-700 dark:focus-within:border-primary-500 dark:focus-within:ring-primary-500 {{ $hasError ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
        {{-- Seeded values render server-side so a validation redirect comes back
             with the user's chips intact rather than an empty box. --}}
        @foreach ($seeded as $value)
            <span data-tag-chip
                  class="inline-flex items-center gap-1 rounded-md bg-gray-200 py-1 pl-2 pr-1 text-sm text-gray-800 dark:bg-gray-600 dark:text-gray-100">
                <span>{{ $value }}</span>
                <button type="button" data-tag-remove
                        class="rounded p-0.5 text-gray-500 hover:bg-gray-300 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:text-gray-300 dark:hover:bg-gray-500 dark:hover:text-white"
                        aria-label="{{ __('Remove :label', ['label' => $value]) }}">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            </span>
        @endforeach

        <input type="text" id="{{ $fieldId }}" data-tag-field autocomplete="off"
               placeholder="{{ $placeholder }}"
               @if ($hasError) aria-invalid="true" aria-describedby="error-{{ $fieldId }}" @endif
               class="min-w-[8rem] flex-1 border-0 bg-transparent p-1 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-gray-400">
    </div>

    {{-- Cloned by the script so a chip added in the browser matches the seeded
         markup exactly. --}}
    <template data-tag-template>
        <span data-tag-chip
              class="inline-flex items-center gap-1 rounded-md bg-gray-200 py-1 pl-2 pr-1 text-sm text-gray-800 dark:bg-gray-600 dark:text-gray-100">
            <span data-tag-label></span>
            <button type="button" data-tag-remove
                    class="rounded p-0.5 text-gray-500 hover:bg-gray-300 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:text-gray-300 dark:hover:bg-gray-500 dark:hover:text-white">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <input type="hidden" name="{{ $name }}">
        </span>
    </template>

    @if ($hasError)
        <p id="error-{{ $fieldId }}" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    <p data-tag-message class="mt-1.5 hidden text-sm text-red-600 dark:text-red-400" role="alert"></p>
</div>