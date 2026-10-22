@props([
    // success | error | warning | info
    'type' => 'info',
    'title' => null,
    'message',
    // How long before it dismisses itself, in milliseconds.
    'duration' => 4000,
    'dismissible' => true,
])

@php
    /**
     * Four tones, each a pale fill with a solid icon.
     *
     * The message is the part that has to be readable, and it is body text
     * rather than an icon, so it needs the full 4.5:1:
     *
     *   light  text-gray-600 on white      7.56:1  AAA
     *   dark   text-gray-300 on gray-800    9.96:1  AAA
     *
     * The icon is a glyph on a pale chip. WCAG 1.4.11 asks 3:1 of a meaningful
     * graphic, and these clear 4.5:1 anyway - measured against the chip each
     * one actually sits on, not against white:
     *
     *   green-700  on green-100    5.71:1
     *   red-700    on red-100      4.89:1
     *   amber-700  on amber-100    4.51:1
     *   primary-700 on primary-100 5.49:1
     *
     * On the dark card the same chips run 7.28:1 to 8.49:1.
     */
    $tones = [
        'success' => [
            'chip' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
            'ring' => 'text-green-700 dark:text-green-400',
            'label' => __('Success'),
            'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'error' => [
            'chip' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200',
            'ring' => 'text-red-700 dark:text-red-400',
            'label' => __('Error'),
            'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
        ],
        'warning' => [
            'chip' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200',
            'ring' => 'text-amber-700 dark:text-amber-400',
            'label' => __('Warning'),
            'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        ],
        'info' => [
            'chip' => 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200',
            'ring' => 'text-primary-700 dark:text-primary-400',
            'label' => __('Notice'),
            'icon' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        ],
    ];

    $state = $tones[$type] ?? $tones['info'];

    // Errors interrupt; everything else waits its turn. The region's own
    // aria-live is polite, and an assertive error overrides it.
    $role = $type === 'error' ? 'alert' : 'status';
@endphp

<div data-toast
     data-toast-type="{{ $type }}"
     data-toast-duration="{{ $duration }}"
     role="{{ $role }}"
     class="pointer-events-auto flex w-full max-w-xs items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 text-gray-600 shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
     {{ $attributes->merge(['class' => '']) }}>

    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $state['chip'] }}">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $state['icon'] }}"/>
        </svg>
    </span>

    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</p>
        @endif

        <p class="text-sm {{ $title ? 'mt-0.5' : '' }}" data-toast-message>{{ $message }}</p>
    </div>

    @if ($dismissible)
        <button type="button" data-toast-close
                class="-m-1.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
            <span class="sr-only">{{ __('Dismiss this notification') }}</span>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</div>