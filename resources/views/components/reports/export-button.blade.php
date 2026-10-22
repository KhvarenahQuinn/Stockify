@props([
    'route',
    // xlsx | pdf
    'format' => 'xlsx',
    'label' => null,
    // Extra query parameters - normally the current filters.
    'filters' => [],
    'tone' => null,
])

@php
    use Illuminate\Support\Facades\Route;

    $label = $label ?? ($format === 'pdf' ? __('Export PDF') : __('Export Excel'));

    /**
     * Two fills, both measured against white text on this project's palette:
     * green-600 is 5.36:1 and red-600 is 4.72:1, so both clear AA for a 14px
     * button label.
     *
     * The format is also named on the button, so the colour is a second signal
     * rather than the only one.
     */
    $tones = [
        'xlsx' => 'bg-green-600 hover:bg-green-700 focus:ring-green-400',
        'pdf' => 'bg-red-600 hover:bg-red-700 focus:ring-red-400',
    ];

    $toneClass = $tones[$tone ?? $format] ?? $tones['xlsx'];

    // Route::has guards the endpoint: a report view whose export route has not
    // been registered yet renders the button disabled rather than throwing.
    $available = Route::has($route);
    $url = $available ? route($route, array_merge($filters, ['format' => $format])) : '#';
@endphp

@if ($available)
    <a href="{{ $url }}"
       data-export-trigger
       data-export-format="{{ $format }}"
       class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white {{ $toneClass }} focus:outline-none focus:ring-2">
        <span data-export-label>{{ $label }}</span>
    </a>
@else
    <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400"
          title="{{ __('The export endpoint is not available.') }}">
        {{ $label }}
    </span>
@endif