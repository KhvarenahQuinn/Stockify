@extends('layouts.app')

@section('content')
    @php
        /**
         * Master/detail workspace. Selection rides on a query string rather than
         * JS state so a manager deep-linking a spec gets the same view, and so
         * the read-only state works with JavaScript switched off entirely.
         *
         * `$specs` is not named `$attributes`: Blade reserves that variable for
         * component attribute bags, and shadowing it breaks the <x-form.*> fields.
         */
        $specs = $specs ?? collect();

        // The controller hands over a paginator, but a half-wired endpoint may
        // pass a plain collection, so totals are read through whichever it is.
        $paginator = $specs instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $specs : null;
        $total = $paginator?->total() ?? $specs->count();

        // Ask the policy per ability instead of testing the role name here: the
        // policy is the one place that decides who may write master data, and a
        // duplicated role check silently drifts away from it.
        $viewer = auth()->user();

        $canCreate = ($viewer?->can('create', \App\Models\Attribute::class) ?? false) && Route::has('attributes.store');
        $canUpdate = ($viewer?->can('update', new \App\Models\Attribute) ?? false) && Route::has('attributes.update');
        $canDelete = ($viewer?->can('delete', new \App\Models\Attribute) ?? false) && Route::has('attributes.destroy');
        $canAddValue = ($viewer?->can('update', new \App\Models\Attribute) ?? false) && Route::has('attributes.values.store');
        $canDeleteValue = ($viewer?->can('update', new \App\Models\Attribute) ?? false) && Route::has('attributes.values.destroy');
        $canManage = $canCreate || $canUpdate || $canDelete;

        $indexUrl = Route::has('attributes.index') ? route('attributes.index') : url()->current();

        // Fall back to the first attribute on the page so the detail pane is
        // never blank just because no ?attribute= was passed.
        $selected = $selectedAttribute ?? $specs->first();
        $selectedId = $selected?->id;

        $selectedValues = $selected
            ? ($selected->relationLoaded('values') ? $selected->values : collect())
            : collect();
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Attributes') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __($total === 1 ? ':count attribute' : ':count attributes', ['count' => format_number($total)]) }}
                    <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                    {{ __('product specifications and their allowed values') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @unless ($canManage)
                    <x-master.read-only-note feature="attributes" />
                @endunless

                @if ($canCreate)
                    <button type="button" data-modal-target="create-attribute-modal" data-modal-toggle="create-attribute-modal"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        {{ __('Add attribute') }}
                    </button>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ $indexUrl }}"
              class="mb-4 flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:flex-row sm:items-center dark:border-gray-700 dark:bg-gray-800">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
                <label for="q" class="sr-only">{{ __('Search attributes') }}</label>
                <input type="search" name="q" id="q" value="{{ request('q') }}" placeholder="{{ __('Search attribute name') }}"
                   class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 pl-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                    {{ __('Search') }}
                </button>
                @if (request()->hasAny(['q', 'attribute']))
                    <a href="{{ $indexUrl }}"
                       class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        {{ __('Clear') }}
                    </a>
                @endif
            </div>
        </form>

        <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-12">
            {{-- Attribute directory --}}
            <div class="lg:col-span-4">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:text-white">
                        {{ __('Attribute directory') }}
                    </h2>

                    <ul class="max-h-[32rem] divide-y divide-gray-200 overflow-y-auto dark:divide-gray-700">
                        @forelse ($specs as $attribute)
                            @php $isSelected = $attribute->id === $selectedId; @endphp
                            <li>
                                <a href="{{ $indexUrl }}?{{ http_build_query(array_filter(['q' => request('q'), 'attribute' => $attribute->id])) }}"
                                   @if ($isSelected) aria-current="true" @endif
                                   @class([
                                       'flex items-center justify-between gap-3 px-4 py-3 text-sm transition-colors',
                                       'border-l-4 border-primary-600 bg-primary-50 font-medium text-gray-900 dark:border-primary-500 dark:bg-gray-700/60 dark:text-white' => $isSelected,
                                       'border-l-4 border-transparent text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/40' => ! $isSelected,
                                   ])>
                                    <span class="truncate">{{ $attribute->name }}</span>
                                    <span @class([
                                        'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums',
                                        'bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-200' => $isSelected,
                                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => ! $isSelected,
                                    ])>
                                        {{ $attribute->values->count() }}
                                    </span>
                                </a>
                            </li>
                        @empty
                            <li class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if (request('q'))
                                    {{ __('No attributes match “:query”.', ['query' => request('q')]) }}
                                @else
                                    {{ __('No attributes yet.') }}
                                @endif
                            </li>
                        @endforelse
                    </ul>

                    @if ($paginator?->hasPages())
                        <div class="border-t border-gray-200 p-3 dark:border-gray-700">
                            {{ $paginator->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Detail pane for the selected attribute --}}
            <div class="lg:col-span-8">
                @if ($selected)
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-4 py-4 dark:border-gray-700">
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-gray-900 dark:text-white">{{ $selected->name }}</h2>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    @php($valueCount = $selectedValues->count())
                                    {{ __($valueCount === 1 ? ':count value available to products' : ':count values available to products', ['count' => $valueCount]) }}
                                </p>
                            </div>

                            @if ($canUpdate || $canDelete)
                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($canUpdate)
                                        <button type="button"
                                                data-modal-target="edit-attribute-{{ $selected->id }}"
                                                data-modal-toggle="edit-attribute-{{ $selected->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                            </svg>
                                            {{ __('Edit') }}
                                        </button>
                                    @endif

                                    @if ($canDelete)
                                        <button type="button"
                                                data-modal-target="delete-attribute-{{ $selected->id }}"
                                                data-modal-toggle="delete-attribute-{{ $selected->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-200 dark:text-red-400 dark:hover:bg-gray-700 dark:focus:ring-red-800/40">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                            {{ __('Delete') }}
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($canAddValue)
                            <form method="POST" action="{{ Route::has('attributes.values.store') ? route('attributes.values.store', $selected) : '#' }}"
                                  class="border-b border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/30">
                                @csrf
                                <input type="hidden" name="_form" value="add-value-form">

                                <label for="new-value" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('Add a value') }}
                                </label>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <input type="text" name="value" id="new-value" maxlength="100" required
                                           placeholder="{{ __('e.g. XXL') }}"
                                           class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                                    <button type="submit"
                                            class="shrink-0 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                                        {{ __('Add value') }}
                                    </button>
                                </div>
                                @error('value')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </form>
                        @endif

                        <div class="p-4">
                            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Values') }}</h3>

                            @if ($selectedValues->isEmpty())
                                <p class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                    {{ __('No values yet.') }}
                                    @if ($canAddValue)
                                        {{ __('Add the first one above.') }}
                                    @endif
                                </p>
                            @else
                                <ul class="flex flex-wrap gap-2">
                                    @foreach ($selectedValues as $attributeValue)
                                        <li class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 py-1 pl-2.5 pr-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <span>{{ $attributeValue->value }}</span>

                                            @if ($canDeleteValue)
                                                <button type="button"
                                                        data-modal-target="delete-value-{{ $attributeValue->id }}"
                                                        data-modal-toggle="delete-value-{{ $attributeValue->id }}"
                                                        class="rounded p-0.5 text-gray-400 hover:bg-gray-200 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:text-gray-500 dark:hover:bg-gray-600 dark:hover:text-red-400"
                                                        aria-label="{{ __('Remove :value from :name', ['value' => $attributeValue->value, 'name' => $selected->name]) }}">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-600 dark:bg-gray-800">
                        <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.569 3z M6 6h.008v.008H6V6z"/>
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                            @if ($canCreate)
                                {{ __('Add an attribute to start defining product specifications.') }}
                            @else
                                {{ __('Nothing to show yet.') }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($canCreate)
        @include('attributes.partials.create-modal')
    @endif

    @if ($selected && $canUpdate)
        @include('attributes.partials.edit-modal', ['attribute' => $selected])
    @endif

    @if ($selected && $canDelete)
        @include('attributes.partials.delete-modal', ['attribute' => $selected])
    @endif

    {{-- One small dialog per value: the chips are too small to carry a native
         confirm, and a refused delete needs room to explain itself. --}}
    @if ($selected && $canDeleteValue)
        @foreach ($selectedValues as $attributeValue)
            @include('attributes.partials.delete-value-modal', [
                'attributeValue' => $attributeValue,
                'attribute' => $selected,
            ])
        @endforeach
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof window.initFlowbite === 'function') {
                window.initFlowbite();
            }

            // Reopen the form that failed validation. The add-value form lives on
            // the page rather than in a modal, so it just needs its error shown.
            const form = @json(old('_form'));

            if (form && form !== 'add-value-form') {
                document.querySelector(`[data-modal-toggle="${form}"]`)?.click();
            }

            /**
             * Chip input. Enter or comma commits the typed text, Backspace on an
             * empty field removes the last chip, and the remove button drops both
             * the chip and its hidden input so the form never posts a value the
             * user already removed.
             */
            document.querySelectorAll('[data-tag-input]').forEach((root) => {
                const chips = root.querySelector('[data-tag-chips]');
                const field = root.querySelector('[data-tag-field]');
                const template = root.querySelector('[data-tag-template]');
                const message = root.querySelector('[data-tag-message]');

                if (!chips || !field || !template) {
                    return;
                }

                const values = () =>
                    Array.from(chips.querySelectorAll('input[type="hidden"]'))
                        .map((input) => input.value.trim().toLowerCase())
                        .filter(Boolean);

                const complain = (text) => {
                    if (!message) {
                        return;
                    }

                    message.textContent = text;
                    message.classList.remove('hidden');
                };

                const clearComplaint = () => message?.classList.add('hidden');

                const commit = () => {
                    const raw = field.value.trim();

                    if (!raw) {
                        return;
                    }

                    // Commas are the other common separator, so a pasted
                    // "S, M, L" turns into three chips instead of one.
                    const parts = raw.split(',').map((part) => part.trim()).filter(Boolean);

                    parts.forEach((part) => {
                        if (values().includes(part.toLowerCase())) {
                            complain(window.t('":label" is already on this list.', { label: part }));
                            return;
                        }

                        const chip = template.content.firstElementChild.cloneNode(true);

                        chip.querySelector('[data-tag-label]').textContent = part;
                        chip.querySelector('input[type="hidden"]').value = part;
                        chip.querySelector('[data-tag-remove]').setAttribute('aria-label', window.t('Remove :label', { label: part }));
                        chips.insertBefore(chip, field);
                    });

                    field.value = '';
                    clearComplaint();
                };

                field.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ',') {
                        // The surrounding form must not submit on Enter; the chip
                        // is the only thing the key means here.
                        event.preventDefault();
                        commit();
                        return;
                    }

                    if (event.key === 'Backspace' && field.value === '') {
                        const chipsPresent = chips.querySelectorAll('[data-tag-chip]');

                        if (chipsPresent.length > 0) {
                            chipsPresent[chipsPresent.length - 1].remove();
                            clearComplaint();
                        }
                    }
                });

                field.addEventListener('blur', commit);
                field.addEventListener('input', clearComplaint);

                // One delegated listener beats rebinding after every mutation.
                chips.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-tag-remove]');

                    if (button) {
                        button.closest('[data-tag-chip]')?.remove();
                        clearComplaint();
                    }
                });
            });
        });
    </script>
@endsection