@props([
    'transaction',
    'canInspect' => false,
    'inspectAction' => null,
])

@php
    $variance = $transaction->inspectionVariance();
    $hasVariance = $variance !== 0;
    $unit = $transaction->product?->unit ?? 'unit';

    // The counterparty is not one field: a receipt names a supplier on master
    // data, an issue names a free-text destination. Resolving both here keeps
    // the row honest instead of saying "Source" and showing a dash half the time.
    $counterparty = $transaction->type === 'IN'
        ? ($transaction->supplier?->name ?? null)
        : ($transaction->destination ?: null);
@endphp

{{--
    What, when, who and from where live in the first column rather than across
    four of them.

    As separate columns they needed 1032px of minimums before a single word of
    content, and the real width came to 1268px against a 1150px content area —
    so the decision buttons ended up off the right edge of a manager's screen.
    Folding the identity into one cell buys the space back without dropping a
    single fact off the ledger.

    Column minimums now total roughly 1016px, which fits a 1440 desktop without
    a scrollbar. The action cell is deliberately not sticky: a pinned right cell
    overlays the status badge whenever content runs wide, and a half-covered
    badge is worse than a scrollbar.
--}}
<tr {{ $attributes->merge(['class' => 'group border-b border-gray-200 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40']) }}>
    <th scope="row" class="min-w-[12rem] px-4 py-3 align-top font-normal">
        <x-transactions.reference :transaction="$transaction" />

        <span @class([
            'mt-1.5 inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold',
            'bg-blue-100 text-blue-900 dark:bg-blue-900/50 dark:text-blue-100' => $transaction->type === 'IN',
            'bg-indigo-100 text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-100' => $transaction->type === 'OUT',
        ])>
            {{ __($transaction->type === 'IN' ? 'Goods in' : 'Goods out') }}
        </span>

        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
            {{ $transaction->created_at?->translatedFormat('d M Y, H:i') }}
            <span class="text-gray-400 dark:text-gray-500">&middot;</span>
            {{ $transaction->user?->name ?? __('Unknown') }}
        </span>

        <span class="mt-0.5 block max-w-[13rem] truncate text-xs text-gray-500 dark:text-gray-400"
              title="{{ $counterparty ?? __('No source recorded') }}">
            {{ $counterparty ?? __('No source recorded') }}
        </span>
    </th>

    <td class="min-w-[9rem] px-4 py-3 align-top">
        <span class="block max-w-[11rem] truncate text-sm font-medium text-gray-900 dark:text-white"
              title="{{ $transaction->product?->name }}">
            {{ $transaction->product?->name ?? __('Product removed') }}
        </span>

        <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] text-gray-700 dark:bg-gray-700 dark:text-gray-200">
            {{ $transaction->product?->sku ?? __('No SKU') }}
        </code>

        @if ($transaction->reference_note)
            <span class="mt-1 block max-w-[11rem] truncate font-mono text-[11px] text-gray-500 dark:text-gray-400"
                  title="{{ $transaction->reference_note }}">
                {{ $transaction->reference_note }}
            </span>
        @endif

        @if ($transaction->document_url)
            <a href="{{ $transaction->document_url }}" target="_blank" rel="noopener"
               class="mt-1 inline-flex max-w-[11rem] items-center gap-1 truncate text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 011.09-7.024 5.25 5.25 0 0110.14 0 5.25 5.25 0 011.09 7.025A4.5 4.5 0 0118 19.5H6.75z"/>
                </svg>
                {{ $transaction->document_name }}
            </a>
        @endif
    </td>

    <td class="min-w-[5.5rem] whitespace-nowrap px-4 py-3 text-right align-top">
        <span class="font-mono text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
            {{ format_number($transaction->quantity) }}
        </span>
        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $unit }}</span>
    </td>

    <td class="min-w-[9rem] px-4 py-3 align-top">
        @if (! $transaction->hasInspection())
            {{-- No sub-line: the status badge two columns over already says
                 "Pending inspection", and repeating it on every uncounted row
                 is what made this list twice as tall as it needed to be. --}}
            <span class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-400 dark:bg-gray-500" aria-hidden="true"></span>
                {{ __('Not counted yet') }}
            </span>
        @else
            <span @class([
                'inline-flex items-center gap-1.5 whitespace-nowrap font-mono text-sm font-semibold tabular-nums',
                'text-gray-900 dark:text-white' => ! $hasVariance,
                'text-red-700 dark:text-red-400' => $hasVariance,
            ])>
                <span @class([
                    'h-1.5 w-1.5 shrink-0 rounded-full',
                    'bg-green-700 dark:bg-green-400' => ! $hasVariance,
                    'bg-red-700 dark:bg-red-400' => $hasVariance,
                ]) aria-hidden="true"></span>
                {{ format_number($transaction->inspected_quantity) }} {{ __('counted') }}
            </span>

            <span class="mt-0.5 block text-xs {{ $hasVariance ? 'font-medium text-red-700 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                @if ($hasVariance)
                    {{ $variance > 0 ? '+' : '' }}{{ $variance }} {{ __('against the request') }}
                @else
                    {{ __('Matches the request') }}
                @endif
            </span>

            @if ($transaction->inspector)
                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                    {{ __('by') }} {{ $transaction->inspector->name }}
                </span>
            @endif
        @endif
    </td>

    <td class="min-w-[11.5rem] px-4 py-3 align-top">
        <x-transactions.status-badge :transaction="$transaction" />

        {{-- No generic "cannot approve this" caption. The Count column already
             states the variance in red and the Approve button is visibly
             disabled; a third copy of the same fact on every flagged row is
             what turned this cell into a wall of identical red text. Only the
             operator's own note earns the space. --}}
        @if ($transaction->inspection_note)
            <span class="mt-1.5 block max-w-[13rem] text-xs italic text-gray-500 dark:text-gray-400" title="{{ $transaction->inspection_note }}">
                &ldquo;{{ $transaction->inspection_note }}&rdquo;
            </span>
        @endif
    </td>

    <td class="min-w-[9.5rem] whitespace-nowrap border-l border-gray-200 px-4 py-3 text-right dark:border-gray-700 align-top">
        {{ $actions }}
    </td>
</tr>