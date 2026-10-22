@props([
    'title',
    'count' => 0,
    // waiting | clear
    'tone' => 'waiting',
    'items' => [],
    'empty' => __('Nothing waiting here.'),
    // Route to the full queue. Null hides the footer link entirely.
    'queueRoute' => null,
    'description' => null,
])

@php
    /**
     * The count is the headline and the rows are the detail, because a manager
     * scanning the dashboard reads "how many" long before "which ones".
     *
     * The figure is coloured text on the card, not a filled chip: a filled
     * badge on every card turns the page into confetti and the colour stops
     * carrying meaning. Measured on this palette - orange #B43403 is 6.12:1 on
     * white, #FDBA8C reads against the gray-800 card.
     */
    $accent = $tone === 'waiting' ? 'warning' : 'matched';
    $hasWork = $count > 0;
@endphp

<section {{ $attributes->merge(['class' => 'flex flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800']) }}
         aria-labelledby="{{ $attributes->get('id', 'task-'.Illuminate\Support\Str::slug($title)) }}">

    <div class="flex items-start justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-700">
        <div class="min-w-0">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
            @if ($description)
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>

        {{-- The number carries a word as well as a colour. --}}
        <x-dashboard.kpi-card :label="$hasWork ? __('Waiting') : __('Clear')"
                              :value="$count"
                              :tone="$hasWork ? $accent : 'matched'"
                              class="shrink-0 border-0 bg-transparent p-0" />
    </div>

    <ul class="flex-1 divide-y divide-gray-100 dark:divide-gray-700">
        @forelse ($items as $item)
            <li class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                <span class="min-w-0 truncate font-mono text-xs text-gray-900 dark:text-white">
                    {{ $item['reference'] ?? '' }}
                </span>
                <span class="min-w-0 flex-1 truncate text-right text-gray-500 dark:text-gray-400">
                    {{ $item['detail'] ?? '' }}
                </span>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ $empty }}</li>
        @endforelse
    </ul>

    @if ($queueRoute && \Illuminate\Support\Facades\Route::has($queueRoute))
        <div class="border-t border-gray-200 p-3 dark:border-gray-700">
            <a href="{{ route($queueRoute) }}"
               class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-400 dark:border-gray-600 dark:text-white dark:hover:bg-gray-700">
                {{ __('Open the full queue') }}
                <span class="sr-only">{{ __('for :title', ['title' => strtolower($title)]) }}</span>
            </a>
        </div>
    @endif
</section>