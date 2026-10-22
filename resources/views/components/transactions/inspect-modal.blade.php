@props([
    'transaction',
    'action' => null,
])

@php
    use App\Models\StockTransaction;

    $modalId = 'inspect-transaction-'.$transaction->id;
    $unit = $transaction->product?->unit ?? __('unit');
    $isUncounted = ! $transaction->hasInspection();
@endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Record the physical count') }}"
               :subtitle="$transaction->reference_number.' · '.$transaction->product?->name"
               max-width="max-w-lg"
               :form-action="$action ?? (Route::has('stock-transactions.inspect') ? route('stock-transactions.inspect', $transaction) : null)"
               form-method="PATCH">
    <x-slot:body>
        {{-- Carried back through the redirect so a validation failure reopens
             this exact modal. --}}
        <input type="hidden" name="_form" value="{{ $modalId }}">

        <div class="space-y-4" data-inspect-form
             data-requested="{{ (int) $transaction->quantity }}"
             data-unit="{{ $unit }}">
            <dl class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-700/50">
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Requested') }}</dt>
                    <dd class="font-mono font-semibold tabular-nums text-gray-900 dark:text-white">
                        {{ format_number($transaction->quantity) }} {{ $unit }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Counted previously') }}</dt>
                    <dd class="font-mono font-semibold tabular-nums text-gray-900 dark:text-white">
                        @if ($transaction->hasInspection())
                            {{ format_number((int) $transaction->inspected_quantity) }}
                        @else
                            {{ __('Not counted yet') }}
                        @endif
                    </dd>
                </div>

                <div class="col-span-2">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Submitted by') }}</dt>
                    <dd class="text-gray-900 dark:text-white">
                        {{ $transaction->user?->name ?? __('Unknown') }}
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $transaction->created_at?->translatedFormat('d M Y, H:i') }}
                        </span>
                    </dd>
                </div>
            </dl>

            <x-form.input name="inspected_quantity"
                          label="{{ __('Units counted on the floor') }}"
                          type="number"
                          inputmode="numeric"
                          :value="$transaction->inspected_quantity ?? $transaction->quantity"
                          required
                          min="0"
                          step="1"
                          data-inspect-quantity
                          aria-describedby="hint-field-inspected_quantity"
                          hint="{{ __('Count what is physically there, not what the paperwork says.') }}" />

            {{-- The live read-out. Server-side the count alone decides whether a
                 discrepancy is recorded, so this never becomes the source of
                 truth — it exists so the operator sees the consequence before
                 pressing the button. --}}
            <p data-inspect-outcome
               role="status"
               class="flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-900 dark:border-green-800 dark:bg-green-900/40 dark:text-green-100">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span data-inspect-outcome-text>{{ __('Count matches the request. This goes to a manager for approval.') }}</span>
            </p>

            <x-form.textarea name="inspection_note"
                             label="{{ __('What did you see?') }}"
                             :rows="3"
                             data-inspect-note
                             :required="false"
                             placeholder="{{ __('e.g. two cartons crushed on the pallet, driver agreed to short-deliver.') }}"
                             hint="{{ __('Required when the count does not match. Optional when it does.') }}" />
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="{{ $modalId }}"
                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Cancel') }}
        </button>

        <button type="submit" data-inspect-submit
                class="rounded-lg bg-primary-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            {{ __($isUncounted ? 'Record count and send to a manager' : 'Update the count') }}
        </button>
    </x-slot:footer>
</x-modal-shell>