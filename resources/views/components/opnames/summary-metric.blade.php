@props([
    'label',
    'value' => 0,
    // neutral | matched | surplus | deficit - drives only the number's colour.
    'tone' => 'neutral',
    'hint' => null,
    // Key the script updates, e.g. "items" / "matched" / "deficit" / "surplus".
    'metric' => null,
])

@php
    /**
     * The figure is coloured text rather than a filled chip, because a tile
     * repeats a number the row badges already state in words - two fills per
     * row turns the sheet into confetti.
     *
     * Each tone ships a -700 for light and a -400 for dark. Measured on white
     * and on the gray-800 surface: green 6.44/7.64, blue 6.18/8.14,
     * red 5.74/7.73.
     *
     * The split is not decorative. The -700 shades land at 2.3-2.6:1 on the dark
     * card and the -400s at 1.8-1.9:1 on white, so either pair on its own would
     * fail in the other theme.
     */
    $tones = [
        'neutral' => 'text-gray-900 dark:text-gray-100',
        'matched' => 'text-green-700 dark:text-green-400',
        'surplus' => 'text-blue-700 dark:text-blue-400',
        'deficit' => 'text-red-700 dark:text-red-400',
    ];

    $toneClass = $tones[$tone] ?? $tones['neutral'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800']) }}
     @if ($metric) data-summary-metric="{{ $metric }}" @endif>
    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
        {{ $label }}
    </p>

    <p class="mt-1 text-2xl font-bold tabular-nums {{ $toneClass }}" data-summary-value>
        {{ is_numeric($value) ? format_number((int) $value) : $value }}
    </p>

    @if ($hint)
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>