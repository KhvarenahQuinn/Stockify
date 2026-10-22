@props([
    'transaction',
    // 'approve' or 'reject'. Both branches share one component so the note
    // field, the summary and the confirm button can never drift apart between
    // the two decisions.
    'decision' => 'approve',
])

@php
    use App\Models\StockTransaction;

    $isApprove = $decision === 'approve';

    $modalId = 'review-'.$decision.'-'.$transaction->id;
    $actionRoute = 'stock-transactions.'.$decision;

    $unit = $transaction->product?->unit ?? __('unit');
    $variance = $transaction->inspectionVariance();
    $blockedByDiscrepancy = $transaction->displayStatus() === StockTransaction::DISPLAY_DISCREPANCY;

    // What the ledger will look like once this lands, so the manager is
    // approving a number rather than an intention.
    $projected = $isApprove && ! $blockedByDiscrepancy
        ? ($transaction->type === 'IN'
            ? (int) $transaction->product?->current_stock + (int) $transaction->quantity
            : (int) $transaction->product?->current_stock - (int) $transaction->quantity)
        : null;
@endphp

<x-modal-shell :id="$modalId"
               :title="$isApprove ? __('Approve movement') : __('Reject movement')"
               :subtitle="$transaction->reference_number.' · '.__('submitted by').' '.($transaction->user?->name ?? __('Unknown'))"
               max-width="max-w-lg"
               :form-action="Route::has($actionRoute) ? route($actionRoute, $transaction) : null"
               form-method="PATCH">
    <x-slot:body>
        {{-- Carried back through the redirect so a validation failure reopens
             this exact modal instead of dumping the manager on a bare page. --}}
        <input type="hidden" name="_form" value="{{ $modalId }}">

        <div class="space-y-4">
            <dl class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-700/50">
                <div class="col-span-2">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Product') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $transaction->product?->name ?? __('Product removed') }}
                        <span class="font-mono text-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $transaction->product?->sku ?? __('No SKU') }}
                        </span>
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Requested') }}</dt>
                    <dd class="font-mono font-semibold tabular-nums text-gray-900 dark:text-white">
                        {{ format_number($transaction->quantity) }} {{ $unit }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('On hand now') }}</dt>
                    <dd class="font-mono font-semibold tabular-nums text-gray-900 dark:text-white">
                        {{ format_number((int) $transaction->product?->current_stock) }} {{ $unit }}
                    </dd>
                </div>

                @if ($transaction->hasInspection())
                    <div class="col-span-2">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Floor count') }}</dt>
                        <dd class="font-mono font-semibold tabular-nums {{ $variance === 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                            {{ format_number((int) $transaction->inspected_quantity) }} {{ $unit }}
                            <span class="font-sans text-xs font-normal text-gray-500 dark:text-gray-400">
                                {{ __('counted by') }} {{ $transaction->inspector?->name ?? __('Unknown') }}
                            </span>
                        </dd>
                    </div>
                @endif

                @if ($projected !== null)
                    <div class="col-span-2">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Stock after approval') }}</dt>
                        <dd class="font-mono text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                            {{ format_number((int) $transaction->product?->current_stock) }}
                            <span class="text-gray-400" aria-hidden="true">&rarr;</span>
                            <span class="rounded bg-gray-200 px-1.5 py-0.5 dark:bg-gray-600">{{ format_number($projected) }}</span>
                            <span class="font-sans text-xs font-normal text-gray-500 dark:text-gray-400">{{ $unit }}</span>
                        </dd>
                    </div>
                @endif
            </dl>

            @if ($blockedByDiscrepancy)
                <p class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-900 dark:border-red-800 dark:bg-red-900/40 dark:text-red-100">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <span>
                        {{ __('The floor count does not match this request. Approving it would post :requested :unit while :counted were counted, so the ledger would disagree with the shelf.', [
                            'requested' => format_number($transaction->quantity),
                            'unit' => $unit,
                            'counted' => format_number((int) $transaction->inspected_quantity),
                        ]) }}
                        <strong class="font-semibold">{{ __('Reject it instead') }}</strong> {{ __('and send it back for a corrected count.') }}
                    </span>
                </p>
            @endif

            <x-form.textarea name="notes"
                             :label="$isApprove ? __('Review note') : __('Reason for rejection')"
                             required
                             :rows="4"
                             :placeholder="$isApprove
                                 ? __('What did you check? e.g. delivery note matches, 48 cartons counted.')
                                 : __('Tell the submitter what to fix. For example: 20 cartons short of the delivery note. Recount with the driver.')"
                             hint="{{ __('Stored against the transaction and visible to the submitter.') }}" />
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="{{ $modalId }}"
                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>

        <button type="submit"
                @disabled($isApprove && $blockedByDiscrepancy)
                class="rounded-lg px-4 py-2.5 text-sm font-medium text-white focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-50 {{ $isApprove
                    ? 'bg-green-700 hover:bg-green-800 focus:ring-green-300 dark:focus:ring-green-900'
                    : 'bg-red-700 hover:bg-red-800 focus:ring-red-300 dark:focus:ring-red-900' }}">
            {{ __($isApprove ? 'Approve and update stock' : 'Reject movement') }}
        </button>
    </x-slot:footer>
</x-modal-shell>