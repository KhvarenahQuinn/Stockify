{{--
    The manager's second pair of eyes.

    Submitting a count saves the physical quantities but leaves the official
    balance alone. This dialog is the only place that tells the service to lock
    the products and post the resulting adjustments, so the consequence is
    stated before the button rather than discovered afterwards.
--}}
@props([
    'sheet',
    'id' => null,
])

@php
    use App\Models\StockOpnameSheet;

    $modalId = $id ?? 'verify-opname-'.$sheet->id;
    $differences = $sheet->items->filter(fn ($item) => (int) $item->variance !== 0);
    $matched = $sheet->items->filter(fn ($item) => (int) $item->variance === 0)->count();
    $net = $sheet->totalNetVariance();
@endphp

@if ($sheet->status === StockOpnameSheet::STATUS_SUBMITTED && Route::has('stock-opnames.verify'))
    <x-modal-shell :id="$modalId"
                   :title="__('Verify & Apply Adjustment')"
                   :subtitle="__('Confirm the physical count before the official balance changes.')"
                   maxWidth="max-w-3xl"
                   :formAction="route('stock-opnames.verify', $sheet)"
                   formMethod="PATCH">

        <x-slot:body>
            <div class="flex gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/40">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-400"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
                </svg>

                <div class="min-w-0 text-sm text-amber-950 dark:text-amber-100">
                    <p class="font-semibold">{{ __('This changes the official stock balance') }}</p>
                    <p class="mt-1">
                        {{ __('Each product row is locked, its balance is set to the physical count, and every difference is written to the movement ledger. This cannot be undone.') }}
                    </p>
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-3 gap-3">
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Lines') }}</dt>
                    <dd class="mt-1 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ format_number($sheet->totalItems()) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Will adjust') }}</dt>
                    <dd class="mt-1 text-xl font-bold tabular-nums text-amber-700 dark:text-amber-400">{{ format_number($differences->count()) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Net variance') }}</dt>
                    <dd class="mt-1 text-xl font-bold tabular-nums {{ $net < 0 ? 'text-red-700 dark:text-red-400' : ($net > 0 ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 dark:text-white') }}">
                        {{ $net > 0 ? '+' : '' }}{{ format_number($net) }}
                    </dd>
                </div>
            </dl>

            @if ($differences->isNotEmpty())
                <div class="mt-5">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Differences that will be posted') }}</h4>

                    <ul class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                        @foreach ($differences as $item)
                            <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $item->product?->name ?? __('Product removed') }}</span>
                                    <span class="block font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item->product?->sku }}</span>
                                </span>
                                <x-opnames.variance-badge :variance="$item->variance" size="sm" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="mt-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-900/30 dark:text-green-100">
                    {{ __('Every line matches the system balance. Verifying records the sign-off without posting a stock adjustment.') }}
                </p>
            @endif

            @if ($matched > 0 && $differences->isNotEmpty())
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ __(':count matched line(s) stay on the sheet and do not create a zero-quantity movement.', ['count' => $matched]) }}
                </p>
            @endif

            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                {{ __('By verifying you confirm the physical count was checked and approve the resulting adjustment. Your name and the verification time are stored on the sheet.') }}
            </p>
        </x-slot:body>

        <x-slot:footer>
            <button type="button" data-modal-hide="{{ $modalId }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                {{ __('Cancel') }}
            </button>
            <button type="submit"
                    class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800 focus:outline-none focus:ring-4 focus:ring-amber-300 dark:focus:ring-amber-900">
                {{ __('Verify & Apply Adjustment') }}
            </button>
        </x-slot:footer>
    </x-modal-shell>
@endif
