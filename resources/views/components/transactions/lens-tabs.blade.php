@props([
    'lens' => 'all',
    'counts' => [],
    'route',
    // Extra query values (type/from/to) carried across so switching lens does
    // not silently throw away the date range someone just picked.
    'search' => [],
])

@php
    /**
     * The four lenses, in the order a request actually travels through them.
     * "Needs a count" leads because for a staff member that is the only thing
     * they can act on.
     */
    $tabs = [
        'all' => __('All open'),
        'inspection' => __('Needs a count'),
        'approval' => __('Ready for approval'),
        'discrepancy' => __('Discrepancy'),
    ];

    $baseUrl = Route::has($route) ? route($route) : url()->current();
    $carried = array_filter($search ?? [], fn ($value) => $value !== null && $value !== '');
@endphp

<div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-4 pt-3 dark:border-gray-700" role="tablist" aria-label="{{ __('Filter queue by workflow stage') }}">
    @foreach ($tabs as $key => $label)
        @php
            $isActive = $lens === $key;
            $count = $counts[$key] ?? null;
            $query = $carried;
            $query['lens'] = $key;

            // The default lens is omitted from the URL so the plain queue link
            // stays clean in a bookmark or a screenshot.
            if ($key === 'all') {
                unset($query['lens']);
            }
        @endphp

        <a href="{{ $baseUrl }}?{{ http_build_query($query) }}"
           @if ($isActive) aria-current="page" @endif
           @class([
               'inline-flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-400',
               'border-primary-600 text-primary-700 dark:border-primary-400 dark:text-primary-300' => $isActive,
               'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-900 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-white' => ! $isActive,
           ])>
            {{ $label }}

            @if ($count !== null)
                <span @class([
                    'rounded-full px-1.5 py-0.5 font-mono text-[11px] font-semibold tabular-nums',
                    'bg-primary-100 text-primary-900 dark:bg-primary-900/50 dark:text-primary-100' => $isActive,
                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => ! $isActive,
                ])>
                    {{ format_number($count) }}
                </span>
            @endif
        </a>
    @endforeach
</div>