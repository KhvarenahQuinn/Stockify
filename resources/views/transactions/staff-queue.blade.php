@extends('layouts.app')

@section('content')
    @php
        use App\Models\StockTransaction;

        /**
         * The floor queue: movements a warehouse operator still has to count.
         *
         * The same rows the manager will later approve sit here first, which is
         * why nothing on this screen changes the stored status — recording a
         * count refines what PENDING means rather than moving the request
         * somewhere else and losing its place in the approval queue.
         */
        $queue = $queue ?? collect();
        $counts = $counts ?? [];
        $filters = $filters ?? [];

        $queueAction = Route::has('stock-transactions.queue')
            ? route('stock-transactions.queue')
            : url()->current();

        // The approvals route is registered for every role, so Route::has() is
        // true even for the staff who would get a 403 from its role middleware.
        // The policy has to decide, or the queue offers staff a dead button.
        $approvalsAction = ($canReview ?? false) && Route::has('stock-transactions.approvals')
            ? route('stock-transactions.approvals')
            : null;

        $historyAction = Route::has('stock-transactions.index')
            ? route('stock-transactions.index')
            : null;

        $canInspect = $canInspect && Route::has('stock-transactions.inspect');

        $waitingOnCount = $counts['inspection'] ?? 0;
        $heldForReview = $counts['discrepancy'] ?? 0;
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Inspection queue') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Count what actually arrived or left, then hand it to a manager.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($historyAction)
                    <a href="{{ $historyAction }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        {{ __('History') }}
                    </a>
                @endif

                @if ($approvalsAction)
                    <a href="{{ $approvalsAction }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                        {{ __('Approval queue') }}
                        @if (($counts['all'] ?? 0) > 0)
                            <span class="rounded-full bg-white/20 px-1.5 py-0.5 font-mono text-[11px] tabular-nums">
                                {{ format_number($counts['all']) }}
                            </span>
                        @endif
                    </a>
                @endif
            </div>
        </div>

        @unless ($canInspect)
            <div class="mb-6">
                <x-master.read-only-note feature="inspections" />
            </div>
        @endunless

        {{-- Two numbers, not four. "All open" is the sum of the other two plus
             what a manager still has to see, and restating it here would be a
             third place to keep in step with the query behind it. --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('Waiting on a count') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                        {{ format_number($waitingOnCount) }}
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            {{ __($waitingOnCount === 1 ? 'movement' : 'movements') }}
                        </span>
                    </p>
                </div>

                <span @class([
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                    'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200' => $waitingOnCount > 0,
                    'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => $waitingOnCount === 0,
                ])>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m-3-8h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('Counted, held for a manager') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                        {{ format_number($heldForReview) }}
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            {{ __($heldForReview === 1 ? 'discrepancy' : 'discrepancies') }}
                        </span>
                    </p>
                </div>

                <span @class([
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                    'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' => $heldForReview > 0,
                    'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => $heldForReview === 0,
                ])>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <x-transactions.lens-tabs :lens="$lens"
                                       :counts="$counts"
                                       route="stock-transactions.queue"
                                       :search="$filters" />

            <form method="GET" action="{{ $queueAction }}" class="flex flex-col gap-3 border-b border-gray-200 p-4 lg:flex-row lg:items-end dark:border-gray-700">
                @if ($lens !== 'all')
                    <input type="hidden" name="lens" value="{{ $lens }}">
                @endif

                <div class="sm:w-48">
                    <label for="queue-type" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Direction') }}
                    </label>
                    <select name="type" id="queue-type"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('Both directions') }}</option>
                        <option value="IN" @selected(($filters['type'] ?? '') === 'IN')>{{ __('Goods in') }}</option>
                        <option value="OUT" @selected(($filters['type'] ?? '') === 'OUT')>{{ __('Goods out') }}</option>
                    </select>
                </div>

                <div class="sm:w-44">
                    <label for="queue-from" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Submitted from') }}
                    </label>
                    <input type="date" name="from" id="queue-from" value="{{ $filters['from'] ?? '' }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="sm:w-44">
                    <label for="queue-to" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('to') }}
                    </label>
                    <input type="date" name="to" id="queue-to" value="{{ $filters['to'] ?? '' }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        {{ __('Filter') }}
                    </button>

                    @if (array_filter($filters))
                        <a href="{{ $queueAction }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <caption class="sr-only">{{ __('Goods movements awaiting a physical count') }}</caption>

                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="min-w-[12rem] px-4 py-3">{{ __('Reference') }}</th>
                            <th scope="col" class="min-w-[9rem] px-4 py-3">{{ __('Product') }}</th>
                            <th scope="col" class="min-w-[5.5rem] whitespace-nowrap px-4 py-3 text-right">{{ __('Requested') }}</th>
                            <th scope="col" class="min-w-[9rem] px-4 py-3">{{ __('Count') }}</th>
                            <th scope="col" class="min-w-[11.5rem] px-4 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="min-w-[9.5rem] border-l border-gray-200 px-4 py-3 text-right dark:border-gray-700"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($queue as $transaction)
                            <x-transactions.queue-row :transaction="$transaction">
                                <x-slot:actions>
                                    @if ($canInspect && ! $transaction->hasInspection())
                                        <button type="button"
                                                data-modal-target="inspect-transaction-{{ $transaction->id }}"
                                                data-modal-toggle="inspect-transaction-{{ $transaction->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-amber-700 px-2.5 py-1.5 text-sm font-medium text-white hover:bg-amber-800 focus:outline-none focus:ring-4 focus:ring-amber-300 dark:focus:ring-amber-900">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ __('Record count') }}
                                        </button>
                                    @elseif ($canInspect)
                                        <button type="button"
                                                data-modal-target="inspect-transaction-{{ $transaction->id }}"
                                                data-modal-toggle="inspect-transaction-{{ $transaction->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                            </svg>
                                            {{ __('Recount') }}
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Read-only') }}</span>
                                    @endif
                                </x-slot:actions>
                            </x-transactions.queue-row>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    @if (array_filter($filters) || $lens !== 'all')
                                        <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('Nothing here.') }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('No movement matches this lens and these dates.') }}
                                        </p>
                                    @else
                                        <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('The queue is clear.') }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Every submitted movement has been counted and is with a manager.') }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($queue, 'hasPages') && $queue->hasPages())
                <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                    {{ $queue->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Which dialog to reopen after a validation bounce. It has to be handed
         over as a single marker: this page renders one inspect dialog per row,
         so transaction-ui.js cannot go looking for the first hidden `_form`
         input and would put the operator back in someone else's count. --}}
    <span data-modal-restore="{{ old('_form') }}" hidden></span>

    @if ($canInspect)
        @foreach ($queue as $transaction)
            <x-transactions.inspect-modal :transaction="$transaction" />
        @endforeach
    @endif
@endsection