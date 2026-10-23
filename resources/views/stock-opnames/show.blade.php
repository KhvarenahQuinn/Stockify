@extends('layouts.app')

@section('content')
@php
    use App\Models\StockOpnameSheet;

    $net = $sheet->totalNetVariance();

    // Passed as a tone name rather than a class string: Tailwind scans source
    // text, so a class interpolated from a variable would never be emitted.
    $netTone = $net > 0 ? 'surplus' : ($net < 0 ? 'deficit' : 'neutral');

    $canVerify = $sheet->isAwaitingVerification()
        && (auth()->user()?->can('verify', $sheet) ?? false)
        && Route::has('stock-opnames.verify');
@endphp

<div class="p-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-mono text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $sheet->reference }}</p>
            <h1 class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('Counted') }} {{ $sheet->audit_date?->translatedFormat('d M Y') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Counted by') }} {{ $sheet->auditor?->name ?? __('an unknown auditor') }}.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-opnames.status-badge :sheet="$sheet" />

            @if ($canVerify)
                <button type="button"
                        data-modal-target="verify-opname-{{ $sheet->id }}"
                        data-modal-toggle="verify-opname-{{ $sheet->id }}"
                        class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800 focus:outline-none focus:ring-4 focus:ring-amber-300 dark:focus:ring-amber-900">
                    {{ __('Verify & Apply Adjustment') }}
                </button>
            @endif

            @if (Route::has('stock-opnames.index'))
                <a href="{{ route('stock-opnames.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                    {{ __('Back to history') }}
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <x-opnames.summary-metric label="{{ __('Items audited') }}" :value="$sheet->totalItems()" />
        <x-opnames.summary-metric label="{{ __('Matched') }}" tone="matched" :value="$sheet->totalMatched()" />
        <x-opnames.summary-metric label="{{ __('Deficit') }}" tone="deficit" :value="$sheet->totalDeficit()" />
        <x-opnames.summary-metric label="{{ __('Surplus') }}" tone="surplus" :value="$sheet->totalSurplus()" />
        <x-opnames.summary-metric label="{{ __('Net variance') }}" :value="($net > 0 ? '+' : '').$net"
                                   :tone="$netTone" class="col-span-2 sm:col-span-1" />
    </div>

    @if ($sheet->isAwaitingVerification())
        <div class="mt-4 flex gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/40">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-400"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
            </svg>

            <div class="min-w-0 text-sm text-amber-950 dark:text-amber-100">
                <p class="font-semibold">{{ __('Awaiting manager verification') }}</p>
                <p class="mt-1">
                    {{ __('The official stock balance has not changed. A manager or admin must verify this sheet before its differences are posted to the movement ledger.') }}
                </p>
            </div>
        </div>
    @elseif ($sheet->isApplied())
        <div class="mt-4 flex gap-3 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/30">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-green-700 dark:text-green-400"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>

            <div class="min-w-0 text-sm text-green-950 dark:text-green-100">
                <p class="font-semibold">{{ __('Applied to the official stock balance') }}</p>
                <p class="mt-1">
                    {{ __('Verified by') }}
                    <span class="font-medium">{{ $sheet->verifier?->name ?? __('a manager') }}</span>
                    @if ($sheet->verified_at)
                        {{ __('on') }} {{ $sheet->verified_at->translatedFormat('d M Y, H:i') }}
                    @endif
                    {{ __('Every difference was written to the movement ledger.') }}
                </p>
            </div>
        </div>
    @endif

    @if ($sheet->notes)
        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Notes') }}</h2>
            <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $sheet->notes }}</p>
        </div>
    @endif

    <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <caption class="sr-only">
                {{ __('Every product counted on this sheet with system stock, physical count, difference and reason') }}
            </caption>
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th scope="col" class="px-6 py-3">{{ __('Product') }}</th>
                    <th scope="col" class="px-6 py-3 text-right">{{ __('System') }}</th>
                    <th scope="col" class="px-6 py-3 text-right">{{ __('Physical') }}</th>
                    <th scope="col" class="px-6 py-3 text-right">{{ __('Difference') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Reason') }}</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($sheet->items as $item)
                    <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-700">
                        <th scope="row" class="px-6 py-4 font-normal">
                            <span class="block font-medium text-gray-900 dark:text-white">
                                {{ $item->product?->name ?? __('Product removed') }}
                            </span>
                            <span class="block font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $item->product?->sku }}
                            </span>
                        </th>

                        <td class="px-6 py-4 text-right tabular-nums">{{ format_number($item->system_stock) }}</td>
                        <td class="px-6 py-4 text-right tabular-nums">{{ format_number($item->physical_stock) }}</td>

                        <td class="px-6 py-4 text-right">
                            <x-opnames.variance-badge :variance="$item->variance" size="sm" />
                        </td>

                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                            {{ $item->reason ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('This sheet has no counted lines.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($canVerify)
    <x-opnames.verify-modal :sheet="$sheet" />
@endif
@endsection