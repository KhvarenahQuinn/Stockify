@extends('layouts.app')

@section('content')
    @php
        use App\Models\StockTransaction;

        /**
         * The manager's decision queue.
         *
         * The controller has already refused this page for anyone below
         * warehouse_manager, so unlike the staff queue there is no read-only
         * branch here — a control that can never render would be dead weight.
         * Approval and rejection still go through Route::has() guards, because a
         * deployment can expose the page before it exposes the endpoints.
         */
        $queue = $queue ?? collect();
        $counts = $counts ?? [];
        $filters = $filters ?? [];

        $approvalsAction = Route::has('stock-transactions.approvals')
            ? route('stock-transactions.approvals')
            : url()->current();

        $queueAction = Route::has('stock-transactions.queue')
            ? route('stock-transactions.queue')
            : null;

        $historyAction = Route::has('stock-transactions.index')
            ? route('stock-transactions.index')
            : null;

        $canApprove = Route::has('stock-transactions.approve');
        $canReject = Route::has('stock-transactions.reject');
        $canDecide = $canApprove && $canReject;

        $readyToApprove = $counts['approval'] ?? 0;
        $blocked = $counts['discrepancy'] ?? 0;
        $uncounted = $counts['inspection'] ?? 0;
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Approval queue') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Every movement waiting on a decision. Approving posts the stock change; rejecting sends it back.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($historyAction)
                    <a href="{{ $historyAction }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        {{ __('Approval history') }}
                    </a>
                @endif

                @if ($queueAction)
                    <a href="{{ $queueAction }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        {{ __('Inspection queue') }}
                        @if ($uncounted > 0)
                            <span class="rounded-full bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] tabular-nums text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                {{ format_number($uncounted) }}
                            </span>
                        @endif
                    </a>
                @endif
            </div>
        </div>

        @unless ($canDecide)
            <p class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-100">
                {{ __('This deployment exposes the queue without the approve and reject endpoints, so decisions cannot be recorded from here.') }}
            </p>
        @endunless

        {{-- The split a manager actually acts on: what is clean, what is stuck.
             "Still uncounted" is deliberately not a headline — it is the floor's
             job, and putting it here would read as work on this desk. --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('Counted, ready to approve') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                        {{ format_number($readyToApprove) }}
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            {{ __($readyToApprove === 1 ? 'movement' : 'movements') }}
                        </span>
                    </p>
                </div>

                <span @class([
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                    'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' => $readyToApprove > 0,
                    'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => $readyToApprove === 0,
                ])>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('Needs a decision on a mismatch') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                        {{ format_number($blocked) }}
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            {{ __($blocked === 1 ? 'discrepancy' : 'discrepancies') }}
                        </span>
                    </p>
                </div>

                <span @class([
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                    'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' => $blocked > 0,
                    'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => $blocked === 0,
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
                                       route="stock-transactions.approvals"
                                       :search="$filters" />

            <form method="GET" action="{{ $approvalsAction }}" class="flex flex-col gap-3 border-b border-gray-200 p-4 lg:flex-row lg:items-end dark:border-gray-700">
                @if ($lens !== 'all')
                    <input type="hidden" name="lens" value="{{ $lens }}">
                @endif

                <div class="sm:w-48">
                    <label for="approval-type" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Direction') }}
                    </label>
                    <select name="type" id="approval-type"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('Both directions') }}</option>
                        <option value="IN" @selected(($filters['type'] ?? '') === 'IN')>{{ __('Goods in') }}</option>
                        <option value="OUT" @selected(($filters['type'] ?? '') === 'OUT')>{{ __('Goods out') }}</option>
                    </select>
                </div>

                <div class="sm:w-44">
                    <label for="approval-from" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Submitted from') }}
                    </label>
                    <input type="date" name="from" id="approval-from" value="{{ $filters['from'] ?? '' }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="sm:w-44">
                    <label for="approval-to" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('to') }}
                    </label>
                    <input type="date" name="to" id="approval-to" value="{{ $filters['to'] ?? '' }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        {{ __('Filter') }}
                    </button>

                    @if (array_filter($filters))
                        <a href="{{ $approvalsAction }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <caption class="sr-only">{{ __('Goods movements awaiting manager approval') }}</caption>

                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="min-w-[12rem] px-4 py-3">{{ __('Reference') }}</th>
                            <th scope="col" class="min-w-[9rem] px-4 py-3">{{ __('Product') }}</th>
                            <th scope="col" class="min-w-[5.5rem] whitespace-nowrap px-4 py-3 text-right">{{ __('Requested') }}</th>
                            <th scope="col" class="min-w-[9rem] px-4 py-3">{{ __('Count') }}</th>
                            <th scope="col" class="min-w-[11.5rem] px-4 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="min-w-[9.5rem] border-l border-gray-200 px-4 py-3 text-right dark:border-gray-700"><span class="sr-only">{{ __('Decision') }}</span></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($queue as $transaction)
                            <x-transactions.queue-row :transaction="$transaction">
                                <x-slot:actions>
                                    @if ($canDecide)
                                        @php
                                            $awaitingInspection = $transaction->displayStatus() === StockTransaction::DISPLAY_PENDING_INSPECTION;
                                        @endphp

                                        <div class="flex flex-col items-end gap-1">
                                            <div class="inline-flex items-center justify-end gap-1">
                                                <button type="button"
                                                        data-modal-target="review-approve-{{ $transaction->id }}"
                                                        data-modal-toggle="review-approve-{{ $transaction->id }}"
                                                        @disabled($awaitingInspection || $transaction->displayStatus() === StockTransaction::DISPLAY_DISCREPANCY)
                                                        title="{{ $awaitingInspection ? __('Floor count required before approval.') : null }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg bg-green-700 px-2.5 py-1.5 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-green-900">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                    </svg>
                                                    {{ __('Approve') }}
                                                </button>

                                                <button type="button"
                                                        data-modal-target="review-reject-{{ $transaction->id }}"
                                                        data-modal-toggle="review-reject-{{ $transaction->id }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-700 px-2.5 py-1.5 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    {{ __('Reject') }}
                                                </button>
                                            </div>

                                            @if ($awaitingInspection)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-900 dark:bg-amber-900/50 dark:text-amber-100">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-700 dark:bg-amber-400" aria-hidden="true"></span>
                                                    {{ __('Floor count required before approval.') }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('No decision endpoint') }}</span>
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
                                        <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('Nothing waiting on you.') }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Every movement has been approved or rejected.') }}
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

    {{-- Which dialog to reopen after a validation bounce. This page renders an
         approve and a reject dialog for every row, so the id has to be handed
         over as a single marker rather than found by searching for the first
         hidden `_form` input on the page. --}}
    <span data-modal-restore="{{ old('_form') }}" hidden></span>

    @if ($canDecide)
        @foreach ($queue as $transaction)
            <x-transactions.review-modal :transaction="$transaction" decision="approve" />
            <x-transactions.review-modal :transaction="$transaction" decision="reject" />
        @endforeach
    @endif
{{--
    Deep link for the manager dashboard's approval preview.

    The preview table links here with ?review={id} so "Review" lands on the
    exact dialog for that movement instead of the top of a long queue. It rides
    the same [data-modal-restore] hook the validation redirect already uses, so
    there is one code path that reopens a modal rather than two.

    Coerced to a string and checked against the rendered toggle ids by the
    script: a value that names no dialog on this page is ignored rather than
    matching nothing and leaving a half-open state.
--}}
@php
    $reviewTarget = request()->query('review');
@endphp
@if ($reviewTarget !== null && $reviewTarget !== '')
    <span data-modal-restore="review-approve-{{ (int) $reviewTarget }}" hidden></span>
@endif
@endsection