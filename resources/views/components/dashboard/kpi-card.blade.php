@props([
    'label',
    'value',
    // neutral | primary | matched | surplus | deficit | warning
    // Drives the icon chip and the figure's colour.
    'tone' => 'primary',
    'hint' => null,
    'href' => null,
    // Route name for the card's own link, so a missing endpoint degrades to
    // plain text instead of throwing on route().
    'route' => null,
    'routeParams' => [],
])

@php
    /**
     * The figure is coloured text, not a filled chip.
     *
     * Colours are this project's Flowbite palette, not Tailwind defaults. The
     * -700 shades are for the white card and the -400s for the gray-800 card:
     *
     *   green  #046C4E 6.44:1 light / #4ADE80 6.58:1 dark
     *   blue   #1A56DB 6.18:1 light / #76A9FA 6.17:1 dark
     *   orange #B43403 6.12:1 light / #FDBA8C ...
     *
     * Reusing one shade for both themes would drop to 2.3-2.6:1 on the dark
     * card, which is why every tone carries both.
     */
    $tones = [
        'neutral' => ['figure' => 'text-gray-900 dark:text-gray-100', 'chip' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'],
        'primary' => ['figure' => 'text-primary-700 dark:text-primary-300', 'chip' => 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200'],
        'matched' => ['figure' => 'text-green-700 dark:text-green-400', 'chip' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
        'surplus' => ['figure' => 'text-blue-700 dark:text-blue-400', 'chip' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
        'deficit' => ['figure' => 'text-red-700 dark:text-red-400', 'chip' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
        'warning' => ['figure' => 'text-orange-700 dark:text-orange-400', 'chip' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'],
    ];

    $state = $tones[$tone] ?? $tones['primary'];

    // A numeric value must not reflow the row as it changes: a fixed width for
    // the unit stops "9" turning into "1,204" and shoving the label sideways.
    $isNumber = is_numeric($value);
    $display = $isNumber ? format_number((float) $value, (float) $value == (int) $value ? 0 : 2) : $value;

    $url = $route && \Illuminate\Support\Facades\Route::has($route)
        ? route($route, $routeParams)
        : $href;
@endphp

<{{ $url ? 'a' : 'div' }}
    @if ($url) href="{{ $url }}" @endif
    {{ $attributes->merge(['class' => 'block rounded-lg border border-gray-200 bg-white p-4 transition dark:border-gray-700 dark:bg-gray-800'.($url ? ' hover:border-primary-400 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-400 dark:hover:border-primary-500' : '')]) }}>

    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
        {{ $label }}
    </p>

    <p class="mt-1.5 text-2xl font-bold tabular-nums {{ $state['figure'] }}">
        {{ $display }}
        @if ($isNumber && $value >= 1000000)
            <span class="sr-only">{{ __('million') }}</span>
        @endif
    </p>

    @if ($hint)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</{{ $url ? 'a' : 'div' }}>