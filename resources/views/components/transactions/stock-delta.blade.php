@props(['transaction'])

@php
    $isPending = $transaction->status === 'PENDING';

    // The delta is signed against the direction of travel, so an OUT reads as a
    // fall at a glance without anyone having to subtract in their head.
    $delta = (int) $transaction->stock_after - (int) $transaction->stock_before;
    $deltaLabel = ($delta > 0 ? '+' : '').format_number($delta);

    $deltaTone = $delta > 0
        ? 'text-green-700 dark:text-green-400'
        : ($delta < 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-600 dark:text-gray-300');
@endphp

@if ($isPending)
    {{-- A pending row has no before/after yet: the count exists, the posting
         does not. Showing 0 → 0 here would read as "no change". The reason goes
         in the title and the accessible name rather than a second visible line,
         which on a 15-row ledger was a wall of the same eight words. --}}
    <span class="inline-flex flex-col">
        <span class="font-mono text-xs text-gray-400 dark:text-gray-500"
              title="{{ __('No stock has moved yet. The before and after figures are written when a manager approves this movement.') }}">
            {{ __('Not posted') }}
            <span class="sr-only"> ({{ __('no stock has moved yet; the snapshot is written on approval') }})</span>
        </span>
    </span>
@else
    <span class="inline-flex flex-col gap-1">
        <span class="inline-flex items-center gap-1.5 font-mono text-xs tabular-nums">
            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                {{ format_number($transaction->stock_before) }}
            </span>
            <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">&rarr;</span>
            <span class="rounded bg-gray-200 px-1.5 py-0.5 font-semibold text-gray-900 dark:bg-gray-600 dark:text-white">
                {{ format_number($transaction->stock_after) }}
            </span>
        </span>
        <span class="font-mono text-[11px] font-semibold tabular-nums {{ $deltaTone }}">
            {{ $deltaLabel }}
            <span class="font-sans font-normal text-gray-500 dark:text-gray-400">{{ __('on :unit stock', ['unit' => strtolower($transaction->product?->unit ?? __('unit'))]) }}</span>
        </span>
    </span>
@endif