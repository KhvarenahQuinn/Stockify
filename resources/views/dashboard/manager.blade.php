{{--
    Warehouse Manager.

    Operational view: what is about to run out, what has arrived or left today,
    and what is sitting in the approval queue waiting on this person.
--}}

{{-- Low stock alert: a banner, not a card, because "you are about to run out"
     is the one thing on this page that cannot wait to be noticed. --}}
@php
    $lowStockCount = (int) $metrics['low_stock_count'];
    $outOfStock = (int) $metrics['out_of_stock_count'];
    $lowStockItems = $metrics['low_stock_items'] ?? collect();
@endphp

@if ($lowStockCount > 0)
    <section class="mb-6 rounded-lg border border-orange-300 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-950"
             aria-labelledby="low-stock-heading">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-orange-700 dark:text-orange-400"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
                </svg>

                <div>
                    <h2 id="low-stock-heading" class="text-sm font-semibold text-orange-900 dark:text-orange-100">
                        {{ format_number($lowStockCount) }} {{ __($lowStockCount === 1 ? 'product is' : 'products are') }}
                        {{ __('at or below the reorder point') }}
                    </h2>

                    <p class="mt-0.5 text-sm text-orange-800 dark:text-orange-200">
                        @if ($outOfStock > 0)
                            {{ format_number($outOfStock) }} {{ __($outOfStock === 1 ? 'product is' : 'products are') }} {{ __('fully out of stock') }}.
                        @else
                            {{ __('Nothing is fully out of stock yet.') }}
                        @endif
                    </p>
                </div>
            </div>

            @if (Route::has('products.index'))
                <a href="{{ route('products.index') }}"
                   class="shrink-0 rounded-lg bg-orange-700 px-3 py-2 text-xs font-semibold text-white hover:bg-orange-800 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    {{ __('Reorder now') }}
                </a>
            @endif
        </div>

        {{-- The worst offenders, not the whole list: a banner that scrolls is a
             banner nobody reads. --}}
        @if ($lowStockItems->isNotEmpty())
            <ul class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($lowStockItems->take(6) as $product)
                    @php $shortfall = $product->min_stock - $product->current_stock; @endphp
                    <li class="flex items-center justify-between gap-2 rounded-lg bg-white/70 px-3 py-2 dark:bg-gray-800/60">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ $product->name }}
                            </span>
                            <span class="block font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $product->sku }}
                            </span>
                        </span>

                        <span class="shrink-0 text-right">
                            <span class="block text-sm font-bold tabular-nums {{ $product->current_stock <= 0 ? 'text-red-700 dark:text-red-400' : 'text-orange-700 dark:text-orange-400' }}">
                                {{ $product->current_stock <= 0 ? __('Out') : $shortfall.' '.__('short') }}
                            </span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">
                                {{ $product->current_stock }}/{{ $product->min_stock }}
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>

            @if ($lowStockItems->count() > 6)
                <p class="mt-2 text-xs text-orange-800 dark:text-orange-200">
                    {{ __('Showing the 6 lowest. The rest are on the') }}
                    @if (Route::has('reports.stock'))
                        <a href="{{ route('reports.stock') }}" class="font-medium underline">{{ __('stock report') }}</a>.
                    @else
                        {{ __('stock report') }}.
                    @endif
                </p>
            @endif
        @endif
    </section>
@endif

<section aria-labelledby="manager-today" class="mb-6">
    <h2 id="manager-today" class="sr-only">{{ __("Today's throughput") }}</h2>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-dashboard.kpi-card :label="__('Goods in today')" :value="$metrics['today_in_count']" tone="matched" />
        <x-dashboard.kpi-card :label="__('Goods out today')" :value="$metrics['today_out_count']" tone="warning" />
        <x-dashboard.kpi-card :label="__('Awaiting approval')" :value="$metrics['pending_approvals']"
                              :tone="$metrics['pending_approvals'] > 0 ? 'warning' : 'matched'"
                              route="stock-transactions.approvals" />
        <x-dashboard.kpi-card :label="__('Below reorder point')" :value="$lowStockCount"
                              :tone="$lowStockCount > 0 ? 'deficit' : 'matched'" />
    </div>
</section>

<div class="grid gap-6 lg:grid-cols-2">
    <section aria-labelledby="approvals-heading"
             class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-700">
            <h2 id="approvals-heading" class="text-base font-semibold text-gray-900 dark:text-white">
                {{ __('Approval queue') }}
            </h2>

            @if (Route::has('stock-transactions.approvals'))
                <a href="{{ route('stock-transactions.approvals') }}"
                   class="shrink-0 text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                    {{ __('Open queue') }}
                </a>
            @endif
        </div>

        @if ($metrics['pending_approval_items']->isEmpty())
            <p class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('Nothing is waiting for your approval.') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ __('Movements waiting for a manager decision') }}</caption>
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-4 py-3">{{ __('Reference') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Product') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('Qty') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">
                                <span class="sr-only">{{ __('Review') }}</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($metrics['pending_approval_items'] as $movement)
                            <tr>
                                <th scope="row" class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ $movement->reference_number }}
                                    <span class="block font-sans text-[11px] font-normal text-gray-500 dark:text-gray-400">
                                        {{ __($movement->type === 'IN' ? 'Receipt' : 'Issue') }}
                                    </span>
                                </th>

                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $movement->product?->name ?? __('Product removed') }}
                                </td>

                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ format_number($movement->quantity) }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    {{-- Deep link: the approvals page reopens this
                                         exact dialog via its ?review= parameter. --}}
                                    <a href="{{ Route::has('stock-transactions.approvals')
                                            ? route('stock-transactions.approvals', ['review' => $movement->id])
                                            : '#' }}"
                                       class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-400 dark:border-gray-600 dark:text-white dark:hover:bg-gray-700">
                                        {{ __('Review') }}
                                        <span class="sr-only">{{ $movement->reference_number }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <x-dashboard.stock-movement-chart :chart="$chart" />
</div>