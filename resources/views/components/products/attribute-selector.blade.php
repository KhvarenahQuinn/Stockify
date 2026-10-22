@props([
    // Deliberately not `attributes`: Blade reserves that name for the
    // component's attribute bag, and a prop of the same name overwrites it and
    // blows up with a BadMethodCallException on getAttributes().
    'specs',
    'selected' => [],
    'name' => 'attribute_value_ids[]',
    'trigger' => null,
])

@php
    /**
     * The attribute_category pivot now exists, so a category can declare which
     * specifications apply to it. The product form does not filter on it yet and
     * offers every attribute; changing the category re-renders the same set from
     * the embedded payload. Wiring the controller to category-scoped attributes
     * is what would make the re-render visibly different.
     *
     * Each attribute is its own combobox rather than one long checklist: the
     * values are only reachable through a search, so a category with forty
     * values does not push the modal past the viewport.
     */
    $selected = collect($selected)->map(fn ($id) => (string) $id)->all();

    $groups = collect($specs)->map(function ($attribute) {
        return [
            'id' => (int) $attribute->id,
            'name' => $attribute->name,
            // Sorted once here so the dropdown is alphabetical on first paint.
            'values' => $attribute->values
                ->sortBy('value', SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn ($value) => ['id' => (int) $value->id, 'label' => $value->value])
                ->values(),
        ];
    })->values();

    // One combobox per attribute, and the component renders once per modal, so
    // both the trigger and the attribute id are needed to keep ids unique.
    $uid = fn (int $attributeId) => 'attr-'.($trigger ?: 'default').'-'.$attributeId;

    $payload = $groups->map(fn ($g) => [
        'id' => $g['id'],
        'name' => $g['name'],
        'values' => collect($g['values'])->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values(),
    ])->values();
@endphp

<div data-attribute-selector
     @if ($trigger) data-attribute-selector="{{ $trigger }}" @endif
     data-attribute-payload="{{ json_encode($payload) }}">
    <div class="mb-1.5 flex items-center justify-between gap-3">
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Attributes') }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400" data-attribute-summary>{{ __('Nothing selected yet') }}</span>
    </div>

    <div data-attribute-fields
         data-attribute-name="{{ $name }}"
         class="max-h-64 space-y-4 overflow-y-auto rounded-lg border border-gray-300 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700">
        @forelse ($groups as $group)
            @php($inputId = $uid($group['id']))
            <div data-attribute-group="{{ $group['id'] }}">
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <label for="{{ $inputId }}"
                           class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                        {{ $group['name'] }}
                    </label>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400" data-attribute-count>0</span>
                </div>

                <div class="relative" data-attribute-combobox>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>

                    <input type="text"
                           id="{{ $inputId }}"
                           data-attribute-search
                           role="combobox"
                           aria-expanded="false"
                           aria-autocomplete="list"
                           aria-controls="{{ $inputId }}-list"
                           autocomplete="off"
                           placeholder="{{ __('Search :name...', ['name' => $group['name']]) }}"
                           class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">

                    {{-- Hidden until the user types or focuses; values already
                         chosen as tags are filtered out by the script.

                         The dark background is one step darker than the modal
                         this floats in (gray-900 vs the shell's gray-800). At
                         gray-800 the panel matched its container exactly, so
                         the list had no visible surface at all and only the
                         hovered row stood out. Shadows barely read on a dark
                         surface, which is what made it look empty until hover. --}}
                    <ul id="{{ $inputId }}-list"
                        data-attribute-dropdown
                        role="listbox"
                        aria-label="{{ __(':name values', ['name' => $group['name']]) }}"
                        class="absolute left-0 right-0 z-20 mt-1 hidden max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                        @foreach ($group['values'] as $value)
                            <li role="none">
                                <button type="button"
                                        role="option"
                                        aria-selected="false"
                                        data-attribute-option
                                        data-value-id="{{ $value['id'] }}"
                                        data-value-label="{{ $value['label'] }}"
                                        class="block w-full px-3 py-2 text-left text-sm text-gray-800 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:bg-gray-700">
                                    {{ $value['label'] }}
                                </button>
                            </li>
                        @endforeach

                        <li role="none" data-attribute-no-match hidden class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No matches') }}
                        </li>
                    </ul>
                </div>

                {{-- Chips. Each carries the checkbox that actually posts. Values the record
                         already has are rendered here so an edit form arrives
                         populated before any script runs; the script only adds
                         the ones picked afterwards. --}}
                <div data-attribute-tags class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($group['values'] as $value)
                        @if (in_array((string) $value['id'], $selected, true))
                            <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 py-0.5 pl-2 pr-1 text-xs font-medium text-primary-900 dark:bg-primary-900/40 dark:text-primary-100"
                                  data-attribute-chip>
                                <label class="flex cursor-pointer items-center gap-1">
                                    <input type="checkbox"
                                           name="{{ $name }}"
                                           value="{{ $value['id'] }}"
                                           checked
                                           data-attribute-chip-input
                                           class="sr-only">
                                    <span>{{ $value['label'] }}</span>
                                </label>
                                <button type="button"
                                        data-attribute-chip-remove
                                        aria-label="{{ __('Remove :label', ['label' => $value['label']]) }}"
                                        class="rounded p-0.5 text-primary-700 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-primary-400 dark:text-primary-200 dark:hover:bg-primary-800">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <p class="py-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('No attributes defined yet.') }}
            </p>
        @endforelse
    </div>

    @error('attribute_value_ids')
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
    @error('attribute_value_ids.*')
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>