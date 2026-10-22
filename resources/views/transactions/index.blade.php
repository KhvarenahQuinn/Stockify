@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Contracts\Pagination\LengthAwarePaginator;

        /**
         * The audit ledger. Every row is a decision someone made, so the table
         * leads with the reference and the status and keeps stock_before /
         * stock_after adjacent as a single before-to-after figure — split across
         * two columns they read as two unrelated numbers.
         */
        $transactions = $transactions ?? collect();
        $filters = $filters ?? [];
        $types = $types ?? [];
        $statuses = $statuses ?? [];

        $indexAction = Route::has('stock-transactions.index')
            ? route('stock-transactions.index')
            : url()->current();

        $createAction = Route::has('stock-transactions.create')
            ? route('stock-transactions.create')
            : null;

        $queueAction = Route::has('stock-transactions.queue')
            ? route('stock-transactions.queue')
            : null;

        $approvalsAction = (auth()->user()?->can('reviewQueue', \App\Models\StockTransaction::class) ?? false)
            && Route::has('stock-transactions.approvals')
                ? route('stock-transactions.approvals')
                : null;

        // The controller hands over a paginator, but a half-wired endpoint may
        // pass a plain collection, so totals are read through whichever it is.
        $paginator = $transactions instanceof LengthAwarePaginator ? $transactions : null;
        $total = $paginator?->total() ?? $transactions->count();

        $hasFilters = array_filter($filters);
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Stock movement history') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __($total === 1 ? ':count movement' : ':count movements', ['count' => format_number($total)]) }}
                    <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                    {{ __('every receipt, issue and the stock it moved') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($queueAction)
                    <a href="{{ $queueAction }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        {{ __('Inspection queue') }}
                    </a>
                @endif

                {{-- The approvals route exists for every role, so Route::has() is
                     true even for the staff its role middleware turns away. The
                     policy decides, or the ledger offers a dead button. --}}
                @if ($approvalsAction)
                    <a href="{{ $approvalsAction }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        {{ __('Approvals') }}
                    </a>
                @endif

                @if ($createAction)
                    <a href="{{ $createAction }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        {{ __('New movement') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ $indexAction }}"
                  class="grid grid-cols-1 gap-3 border-b border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end dark:border-gray-700">
                <div class="lg:col-span-2">
                    <label for="filter-status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Status') }}
                    </label>
                    <select name="status" id="filter-status"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('Every status') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ __(ucfirst(strtolower($status))) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-type" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Direction') }}
                    </label>
                    <select name="type" id="filter-type"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('Both directions') }}</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>
                                {{ __($type === 'IN' ? 'Goods in' : 'Goods out') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-from" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('From') }}
                    </label>
                    <input type="date" name="from" id="filter-from" value="{{ $filters['from'] ?? '' }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label for="filter-to" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('To') }}
                    </label>
                    <input type="date" name="to" id="filter-to" value="{{ $filters['to'] ?? '' }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        {{ __('Filter') }}
                    </button>

                    @if ($hasFilters)
                        <a href="{{ $indexAction }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <caption class="sr-only">{{ __('Stock movement audit ledger') }}</caption>

                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="min-w-[12rem] px-4 py-3">{{ __('Reference') }}</th>
                            <th scope="col" class="min-w-[9rem] px-4 py-3">{{ __('Direction') }}</th>
                            <th scope="col" class="min-w-[11rem] px-4 py-3">{{ __('Product') }}</th>
                            <th scope="col" class="min-w-[5.5rem] whitespace-nowrap px-4 py-3 text-right">{{ __('Requested') }}</th>
                            <th scope="col" class="min-w-[10rem] px-4 py-3">{{ __('Stock before → after') }}</th>
                            <th scope="col" class="min-w-[11.5rem] px-4 py-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr class="border-b border-gray-200 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40">
                                {{-- Submitted-when and submitted-by ride along with the
                                     reference. They were their own column once, and at
                                     this width that column was what pushed the status
                                     badge underneath the scroll. --}}
                                <th scope="row" class="min-w-[12rem] px-4 py-3 align-top font-normal">
                                    <x-transactions.reference :transaction="$transaction" />
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $transaction->created_at?->translatedFormat('d M Y, H:i') }}
                                        <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                                        {{ $transaction->user?->name ?? __('Unknown') }}
                                    </span>
                                </th>

                                <td class="min-w-[9rem] px-4 py-3 align-top">
                                    <span @class([
                                        'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-blue-100 text-blue-900 dark:bg-blue-900/50 dark:text-blue-100' => $transaction->type === 'IN',
                                        'bg-indigo-100 text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-100' => $transaction->type === 'OUT',
                                    ])>
                                        {{ __($transaction->type === 'IN' ? 'Goods in' : 'Goods out') }}
                                    </span>

                                    <span class="mt-1.5 block max-w-[13rem] truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $transaction->type === 'IN'
                                            ? ($transaction->supplier?->name ?? __('No supplier recorded'))
                                            : ($transaction->destination ?: __('No destination recorded')) }}
                                    </span>
                                </td>

                                <td class="min-w-[11rem] px-4 py-3 align-top">
                                    <span class="block max-w-[15rem] truncate font-medium text-gray-900 dark:text-white" title="{{ $transaction->product?->name }}">
                                        {{ $transaction->product?->name ?? __('Product removed') }}
                                    </span>
                                    <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $transaction->product?->sku ?? __('No SKU') }}
                                    </code>
                                </td>

                                <td class="min-w-[5.5rem] whitespace-nowrap px-4 py-3 text-right align-top">
                                    <span class="font-mono text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                                        {{ format_number($transaction->quantity) }}
                                    </span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $transaction->product?->unit ?? __('unit') }}
                                    </span>
                                </td>

                                <td class="min-w-[10rem] px-4 py-3 align-top">
                                    <x-transactions.stock-delta :transaction="$transaction" />
                                </td>

                                <td class="min-w-[11.5rem] px-4 py-3 align-top">
                                    <x-transactions.status-badge :transaction="$transaction" />

                                    @if ($transaction->approver)
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('by') }} {{ $transaction->approver->name }}
                                        </span>
                                    @elseif ($transaction->displayStatus() === \App\Models\StockTransaction::DISPLAY_PENDING_INSPECTION)
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('waiting on a floor count') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    @if ($hasFilters)
                                        <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('No movements match.') }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Nothing recorded for that status, direction or date range.') }}
                                        </p>
                                    @else
                                        <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('No movements yet.') }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Receipts and issues will appear here once someone submits one.') }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($paginator?->hasPages())
                <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection