{{--
    Warehouse Staff.

    Floor view: two queues of things physically waiting on this person, and the
    two forms they will reach for most. No stock value, no user counts - none of
    it is actionable at floor level.
--}}

<section aria-labelledby="staff-shortcuts" class="mb-6">
    <h2 id="staff-shortcuts" class="sr-only">{{ __('Shortcuts') }}</h2>

    <div class="grid gap-4 sm:grid-cols-2">
        @if (Route::has('stock-opnames.create'))
            <a href="{{ route('stock-opnames.create') }}"
               class="group flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 hover:border-primary-400 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-400 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-500">
                <span class="shrink-0 rounded-lg bg-primary-100 p-2 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3"/>
                    </svg>
                </span>

                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                        {{ __('Start a stock opname') }}
                    </span>
                    <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Count what is on the shelf and submit it for verification.') }}
                    </span>
                </span>
            </a>
        @endif

        @if (Route::has('stock-transactions.create'))
            <a href="{{ route('stock-transactions.create') }}"
               class="group flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 hover:border-primary-400 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-400 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-500">
                <span class="shrink-0 rounded-lg bg-primary-100 p-2 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </span>

                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                        {{ __('Record goods in or out') }}
                    </span>
                    <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Log an arrival or a dispatch. A manager reviews it before stock moves.') }}
                    </span>
                </span>
            </a>
        @endif
    </div>
</section>

<section aria-labelledby="staff-queues">
    <h2 id="staff-queues" class="mb-3 text-base font-semibold text-gray-900 dark:text-white">
        {{ __('Waiting on you') }}
    </h2>

    <div class="grid gap-4 lg:grid-cols-2">
        @php
            $incoming = $metrics['pending_incoming'];
            $outgoing = $metrics['pending_outgoing'];

            // The card takes flat rows rather than models so the same component
            // can render either queue without learning about StockTransaction.
            $incomingRows = $incoming->map(fn ($m) => [
                'reference' => $m->reference_number,
                'detail' => ($m->product?->name ?? __('Product removed')).' · '.format_number($m->quantity).' '.($m->product?->unit ?? __('unit')),
            ])->all();

            $outgoingRows = $outgoing->map(fn ($m) => [
                'reference' => $m->reference_number,
                'detail' => ($m->product?->name ?? __('Product removed')).' · '.format_number($m->quantity).' '.($m->product?->unit ?? __('unit')),
            ])->all();
        @endphp

        <x-dashboard.task-card id="queue-incoming"
                               :title="__('Arrivals to inspect')"
                               :description="__('Supplied but not yet checked in.')"
                               :count="$metrics['pending_incoming_count']"
                               :items="$incomingRows"
                               :tone="$metrics['pending_incoming_count'] > 0 ? 'waiting' : 'clear'"
                               :empty="__('No arrivals are waiting to be inspected.')"
                               queue-route="stock-transactions.queue" />

        <x-dashboard.task-card id="queue-outgoing"
                               :title="__('Dispatches to prepare')"
                               :description="__('Approved and waiting to leave the floor.')"
                               :count="$metrics['pending_outgoing_count']"
                               :items="$outgoingRows"
                               :tone="$metrics['pending_outgoing_count'] > 0 ? 'waiting' : 'clear'"
                               :empty="__('No dispatches are waiting to be prepared.')"
                               queue-route="stock-transactions.queue" />
    </div>
</section>