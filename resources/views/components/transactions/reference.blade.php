@props([
    'transaction',
    // Set to false in a table cell that already sits inside a link, or where the
    // detail route has not landed yet.
    'link' => true,
])

@php
    $href = $link && Route::has('stock-transactions.show')
        ? route('stock-transactions.show', $transaction)
        : null;
@endphp

@if ($href)
    <a href="{{ $href }}"
       class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-1 font-mono text-xs font-semibold text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-400 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
        {{-- The arrow is the only thing that changes with direction; the IN/OUT
             word carries it too, so the badge is not a colour-only cue. --}}
        <span aria-hidden="true" class="text-[10px] leading-none text-gray-500 dark:text-gray-400">
            {{ $transaction->type === 'IN' ? '↓' : '↑' }}
        </span>
        {{ $transaction->reference_number }}
        <span class="sr-only"> ({{ __($transaction->type === 'IN' ? 'goods receipt' : 'goods issue') }})</span>
    </a>
@else
    <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-1 font-mono text-xs font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100">
        <span aria-hidden="true" class="text-[10px] leading-none text-gray-500 dark:text-gray-400">
            {{ $transaction->type === 'IN' ? '↓' : '↑' }}
        </span>
        {{ $transaction->reference_number }}
        <span class="sr-only"> ({{ __($transaction->type === 'IN' ? 'goods receipt' : 'goods issue') }})</span>
    </span>
@endif