@props([
    // A signed integer, or any object exposing ->variance so a counted line can
    // be passed straight through.
    'variance' => 0,
    'size' => 'md',
])

@php
    $variance = is_object($variance) ? (int) $variance->variance : (int) $variance;

    /**
     * Three solid fills, white text, no colour-only signalling.
     *
     * Measured against white text: green-700 6.44:1, blue-700 6.18:1,
     * red-700 5.74:1 - all clear WCAG AA for the 11-12px label this renders at.
     *
     * Those figures are this project's palette, not Tailwind's defaults. The
     * Flowbite plugin replaces the whole colour scale, so green-700 is #046c4e
     * rather than #15803d; the shade names match, the hex does not.
     *
     * Blue carries surplus rather than a brighter emerald because DESIGN.md's
     * success green (#0E9F6E) only reaches 3.39:1 on white, and green already
     * means "matched", where it reads as "nothing to do".
     *
     * Every chip carries a word as well as a colour, so the three survive both
     * greyscale printing and red-green colour deficiency.
     */
    $tones = [
        'matched' => ['tone' => 'bg-green-700 text-white', 'dot' => 'bg-green-200', 'word' => __('Match'), 'hint' => ''],
        'surplus' => ['tone' => 'bg-blue-700 text-white', 'dot' => 'bg-blue-200', 'word' => __('found'), 'hint' => __('over system stock')],
        'deficit' => ['tone' => 'bg-red-700 text-white', 'dot' => 'bg-red-200', 'word' => __('short'), 'hint' => __('against system stock')],
    ];

    $kind = $variance > 0 ? 'surplus' : ($variance < 0 ? 'deficit' : 'matched');
    $state = $tones[$kind];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];

    // A signed number is the fastest read; the word after it is what carries the
    // meaning once colour is gone.
    $figure = $kind === 'matched' ? '0' : ($variance > 0 ? '+'.format_number($variance) : format_number($variance));
@endphp

<span data-variance-badge
      data-kind="{{ $kind }}"
      {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full font-semibold tabular-nums '.$state['tone'].' '.($sizes[$size] ?? $sizes['md'])]) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $state['dot'] }}" data-variance-dot aria-hidden="true"></span>

    @if ($kind === 'matched')
        <span data-variance-figure>{{ $figure }}</span>
        <span class="sr-only">, {{ __('stock matches the system figure') }}</span>
    @else
        <span data-variance-figure>{{ $figure }}</span>
        <span class="font-medium opacity-90" data-variance-word>{{ $state['word'] }}</span>
        <span class="sr-only"> {{ __('units') }} {{ $state['hint'] }}</span>
    @endif
</span>