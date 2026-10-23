@extends('layouts.app')

@section('content')
    @php
        use App\Models\StockTransaction;

        $indexAction = Route::has('stock-transactions.index')
            ? route('stock-transactions.index')
            : url()->current();

        $queueAction = Route::has('stock-transactions.queue')
            ? route('stock-transactions.queue')
            : null;

        $isPending = $transaction->status === StockTransaction::STATUS_PENDING;
        $hasDiscrepancy = $transaction->displayStatus() === StockTransaction::DISPLAY_DISCREPANCY;
        $awaitingInspection = $transaction->displayStatus() === StockTransaction::DISPLAY_PENDING_INSPECTION;

        $canDecide = $isPending
            && Route::has('stock-transactions.approve')
            && Route::has('stock-transactions.reject')
            && (auth()->user()?->can('reviewQueue', StockTransaction::class) ?? false);

        $canInspect = $isPending
            && Route::has('stock-transactions.inspect')
            && (auth()->user()?->can('inspect', StockTransaction::class) ?? false);

        $canComplete = $transaction->status === StockTransaction::STATUS_APPROVED
            && Route::has('stock-transactions.complete')
            && (auth()->user()?->can('complete', StockTransaction::class) ?? false);

        $unit = $transaction->product?->unit ?? 'unit';
        $variance = $transaction->inspectionVariance();

        // Seeded accounts are often named after their own role ("Warehouse
        // Staff · Warehouse Staff"). Printing it twice looks like a bug, so the
        // role is only added when it says something the name does not.
        $submitterName = $transaction->user?->name ?? 'Unknown';
        $submitterRole = $transaction->user?->role?->display_name;
        $showRole = $submitterRole && $submitterRole !== $submitterName;
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <nav class="mb-1 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400" aria-label="{{ __('Breadcrumb') }}">
                    <a href="{{ $indexAction }}" class="hover:text-primary-700 hover:underline dark:hover:text-primary-300">
                        {{ __('Stock movement history') }}
                    </a>
                    <span aria-hidden="true">/</span>
                    <span class="font-mono text-gray-700 dark:text-gray-300">{{ $transaction->reference_number }}</span>
                </nav>

                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $transaction->product?->name ?? __('Movement') }}
                </h1>

                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-transactions.status-badge :transaction="$transaction" />
                    <x-transactions.reference :transaction="$transaction" :link="false" />
                </div>
            </div>

            @if ($queueAction && $isPending)
                <a href="{{ $queueAction }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                    {{ __('Inspection queue') }}
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
            <div class="space-y-6 lg:col-span-2">
                <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:bg-gray-700/40 dark:text-white">
                        {{ __('What was requested') }}
                    </h2>

                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 p-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Direction') }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
                                @if ($transaction->is_adjustment)
                                    {{ __($transaction->type === 'IN' ? 'Stock adjustment (increase)' : 'Stock adjustment (decrease)') }}
                                @else
                                    {{ __($transaction->type === 'IN' ? 'Goods receipt (in)' : 'Goods issue (out)') }}
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Quantity') }}</dt>
                            <dd class="mt-0.5 font-mono text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                                {{ format_number($transaction->quantity) }} {{ $unit }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __($transaction->type === 'IN' ? 'Supplier' : 'Destination') }}
                            </dt>
                            <dd class="mt-0.5 text-sm text-gray-900 dark:text-white">
                                {{ $transaction->type === 'IN'
                                    ? ($transaction->supplier?->name ?? __('Not recorded'))
                                    : ($transaction->destination ?: __('Not recorded')) }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Delivery note reference') }}</dt>
                            <dd class="mt-0.5 font-mono text-sm text-gray-900 dark:text-white">
                                {{ $transaction->reference_note ?: __('None given') }}
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Submitted by') }}</dt>
                            <dd class="mt-0.5 text-sm text-gray-900 dark:text-white">
                                {{ $submitterName }}
                                @if ($showRole)
                                    <span class="text-gray-500 dark:text-gray-400">· {{ $submitterRole }}</span>
                                @endif
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $transaction->created_at?->translatedFormat('d M Y, H:i') }}
                                </span>
                            </dd>
                        </div>

                        @if ($transaction->document_url)
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Supporting document') }}</dt>
                                <dd class="mt-0.5">
                                    <a href="{{ $transaction->document_url }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                        </svg>
                                        {{ $transaction->document_name }}
                                    </a>
                                </dd>
                            </div>
                        @endif

                        @if ($transaction->notes)
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Notes from the submitter') }}</dt>
                                <dd class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $transaction->notes }}</dd>
                            </div>
                        @endif

                        @if ($transaction->is_adjustment)
                            <div class="sm:col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/30">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-amber-900 dark:text-amber-200">
                                    {{ __('Adjustment reason') }}
                                </dt>
                                <dd class="mt-1 text-sm text-amber-950 dark:text-amber-100">
                                    {{ $transaction->adjustment_reason ?: __('No reason recorded') }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:bg-gray-700/40 dark:text-white">
                        {{ __('Physical inspection') }}
                    </h2>

                    @if ($transaction->hasInspection())
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 p-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Counted') }}</dt>
                                <dd class="mt-0.5 font-mono text-sm font-semibold tabular-nums {{ $variance === 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                    {{ format_number((int) $transaction->inspected_quantity) }} {{ $unit }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Against the request') }}</dt>
                                <dd class="mt-0.5 font-mono text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {{ $variance > 0 ? '+' : '' }}{{ $variance }} {{ $unit }}
                                </dd>
                            </div>

                            <div class="sm:col-span-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Counted by') }}</dt>
                                <dd class="mt-0.5 text-sm text-gray-900 dark:text-white">
                                    {{ $transaction->inspector?->name ?? __('Unknown') }}
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $transaction->inspected_at?->translatedFormat('d M Y, H:i') }}
                                    </span>
                                </dd>
                            </div>

                            @if ($transaction->inspection_note)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('What the operator saw') }}</dt>
                                    <dd class="mt-0.5 text-sm italic text-gray-900 dark:text-white">
                                        &ldquo;{{ $transaction->inspection_note }}&rdquo;
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="p-4 text-sm text-gray-600 dark:text-gray-300">
                            {{ __('Nobody has counted this yet.') }}
                            @if ($isPending && $queueAction)
                                {{ __('It stays in the') }}
                                <a href="{{ $queueAction }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">
                                    {{ __('inspection queue') }}
                                </a>
                                {{ __('until a warehouse operator records what is physically there.') }}
                            @endif
                        </p>
                    @endif
                </section>
            </div>

            <div class="space-y-4 lg:col-span-1">
                <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Stock snapshot') }}</h2>

                    <div class="mt-3">
                        <x-transactions.stock-delta :transaction="$transaction" />
                    </div>

                    @if (! $isPending)
                        <dl class="mt-4 space-y-2 border-t border-gray-200 pt-3 text-sm dark:border-gray-700">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('On hand now') }}</dt>
                                <dd class="font-mono font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {{ format_number((int) $transaction->product?->current_stock) }} {{ $unit }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Decided by') }}</dt>
                                <dd class="text-right text-gray-900 dark:text-white">
                                    {{ $transaction->approver?->name ?? __('Not decided yet') }}
                                </dd>
                            </div>
                        </dl>
                    @endif

                    @if ($transaction->status === StockTransaction::STATUS_REJECTED && $transaction->notes)
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/40">
                            <p class="text-xs font-semibold text-red-900 dark:text-red-100">{{ __('Reason for rejection') }}</p>
                            <p class="mt-1 text-sm text-red-900 dark:text-red-100">{{ $transaction->notes }}</p>
                        </div>
                    @endif
                </section>

                @if ($canDecide || $canInspect || $canComplete)
                    <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Next step') }}</h2>

                        <div class="mt-3 space-y-2">
                            @if ($canDecide)
                                <button type="button"
                                        data-modal-target="review-approve-{{ $transaction->id }}"
                                        data-modal-toggle="review-approve-{{ $transaction->id }}"
                                        @disabled($hasDiscrepancy || $awaitingInspection)
                                        title="{{ $awaitingInspection ? __('Floor count required before approval.') : null }}"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-green-900">
                                    {{ $awaitingInspection ? __('Floor count required before approval') : __('Approve and update stock') }}
                                </button>

                                @if ($awaitingInspection)
                                    <span class="flex items-start gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">
                                        <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-700 dark:bg-amber-400" aria-hidden="true"></span>
                                        {{ __('A staff member must record the physical count before this movement can be approved.') }}
                                    </span>
                                @endif

                                <button type="button"
                                        data-modal-target="review-reject-{{ $transaction->id }}"
                                        data-modal-toggle="review-reject-{{ $transaction->id }}"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
                                    {{ __('Reject and send back') }}
                                </button>
                            @endif

                            @if ($canInspect)
                                <button type="button"
                                        data-modal-target="inspect-transaction-{{ $transaction->id }}"
                                        data-modal-toggle="inspect-transaction-{{ $transaction->id }}"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-800 focus:outline-none focus:ring-4 focus:ring-amber-300 dark:focus:ring-amber-900">
                                    {{ __($transaction->hasInspection() ? 'Record a new count' : 'Record the physical count') }}
                                </button>
                            @endif

                            @if ($canComplete)
                                <form method="POST"
                                      action="{{ Route::has('stock-transactions.complete') ? route('stock-transactions.complete', $transaction) : '#' }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                                        {{ __('Mark as completed') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </section>
                @elseif ($isPending)
                    <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-300">
                        {{ __('A manager or admin decides this one. Nothing is needed from you.') }}
                    </p>
                @endif

                <a href="{{ $indexAction }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                    {{ __('Back to history') }}
                </a>
            </div>
        </div>
    </div>

    <span data-modal-restore="{{ old('_form') }}" hidden></span>

    @if ($canDecide)
        <x-transactions.review-modal :transaction="$transaction" decision="approve" />
        <x-transactions.review-modal :transaction="$transaction" decision="reject" />
    @endif

    @if ($canInspect)
        <x-transactions.inspect-modal :transaction="$transaction" />
    @endif
@endsection