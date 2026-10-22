@extends('layouts.app')

@section('content')
    @php
        /**
         * Goods Receipt (IN) and Goods Issue (OUT) share one form.
         *
         * Splitting them across two screens would mean two sets of validation
         * rules, two sets of copy and two places for the stock guard to live. The
         * direction only changes which counterparty is required and which way
         * the stock moves, so it is a control on the form rather than a fork in
         * the routes.
         *
         * The store action is handed in by the controller and probed with
         * Route::has(), so this view renders read-only if the endpoint is absent
         * instead of throwing a UrlGenerationException.
         */
        $products = $products ?? collect();
        $suppliers = $suppliers ?? collect();

        $storeAction = $storeAction ?? (Route::has('stock-transactions.store')
            ? route('stock-transactions.store')
            : null);

        $indexAction = Route::has('stock-transactions.index')
            ? route('stock-transactions.index')
            : url()->current();

        $canSubmit = $storeAction !== null;
        $currentType = strtoupper(old('type', 'IN'));
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('New goods movement') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Record what arrived from a supplier or what is leaving for a customer. Stock only moves once a manager approves.') }}
                </p>
            </div>

            <a href="{{ $indexAction }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                {{ __('Transaction history') }}
            </a>
        </div>

        @unless ($canSubmit)
            <p class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-100">
                {{ __('Movement submission is switched off on this deployment. The form below is shown for reference but cannot be sent.') }}
            </p>
        @endunless

        <form method="POST"
              action="{{ $storeAction ?? '#' }}"
              enctype="multipart/form-data"
              data-movement-form>
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-8">
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700">
                            <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 10.5L12 15m0 0l-4.5-4.5M12 15V3"/>
                            </svg>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Movement details') }}</h2>
                        </div>

                        <div class="space-y-5 p-4 sm:p-5">
                            {{-- Direction first: it decides which fields matter and
                                 which way the availability panel reads, so putting
                                 it above the product saves a round trip. --}}
                            <fieldset>
                                <legend class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('Direction') }} <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                </legend>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @foreach (['IN' => __('Goods receipt'), 'OUT' => __('Goods issue')] as $value => $label)
                                        @php
                                            $description = $value === 'IN'
                                                ? __('Stock arrives from a supplier.')
                                                : __('Stock leaves for a customer or branch.');
                                            $isChecked = $currentType === $value;
                                        @endphp
                                        <label @class([
                                            'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors',
                                            'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-900/30' => $isChecked,
                                            'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/50' => ! $isChecked,
                                        ])>
                                            <input type="radio"
                                                   name="type"
                                                   value="{{ $value }}"
                                                   class="mt-0.5 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                                   @checked($isChecked)
                                                   data-movement-type>

                                            <span class="min-w-0">
                                                <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $description }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>

                                @error('type')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </fieldset>

                            <x-transactions.product-combobox :products="$products" :selected-id="null" />

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <x-form.input name="quantity"
                                              label="{{ __('Quantity') }}"
                                              type="number"
                                              inputmode="numeric"
                                              min="1"
                                              step="1"
                                              required
                                              :value="old('quantity', 1)"
                                              hint="{{ __('Whole units, counted in the product\'s own unit.') }}" />

                                <x-form.input name="reference_note"
                                              label="{{ __('Delivery note reference') }}"
                                              :value="old('reference_note')"
                                              placeholder="{{ __('SJ-0001/10/2026/0042') }}"
                                              maxlength="120"
                                              hint="{{ __('The number on the supplier\'s or your own paperwork.') }}" />
                            </div>

                            {{-- Both counterparties render so the form still submits
                                 without JavaScript; transaction-ui.js hides the one
                                 that cannot apply. The server re-checks which is
                                 required, so a hidden field left filled is harmless. --}}
                            <div data-direction-only="IN">
                                <x-form.select name="supplier_id"
                                               label="{{ __('Supplier source') }}"
                                               :options="$suppliers"
                                               :value="old('supplier_id')"
                                               placeholder="{{ __('Choose the supplier') }}"
                                               required
                                               hint="{{ __('Required for a goods receipt. The goods came from here.') }}" />
                            </div>

                            <div data-direction-only="OUT">
                                <x-form.input name="destination"
                                              label="{{ __('Destination') }}"
                                              :value="old('destination')"
                                              placeholder="{{ __('Surabaya branch, DC Rungkut') }}"
                                              maxlength="150"
                                              required
                                              hint="{{ __('Required for a goods issue. Where these units are going.') }}" />
                            </div>

                            <x-form.textarea name="notes"
                                             label="{{ __('Notes for the approver') }}"
                                             :rows="3"
                                             :value="old('notes')"
                                             maxlength="1000"
                                             placeholder="{{ __('Anything the manager should know before deciding.') }}"
                                             hint="{{ __('Goes on the audit trail and is visible to whoever approves this.') }}" />

                            <x-form.document-upload />
                        </div>
                    </div>
                </div>

                <div class="space-y-4 lg:col-span-4">
                    <x-transactions.stock-check :type="$currentType" />

                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/30">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                            <svg class="h-4 w-4 text-primary-700 dark:text-primary-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                            </svg>
                            {{ __('What happens next') }}
                        </h2>

                        <ol class="mt-3 space-y-2.5 text-sm text-gray-700 dark:text-gray-300">
                            <li class="flex gap-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-100 font-mono text-[11px] font-bold text-primary-900 dark:bg-primary-900/50 dark:text-primary-100">1</span>
                                <span>{{ __('You submit this. It lands in the inspection queue as pending.') }}</span>
                            </li>
                            <li class="flex gap-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-100 font-mono text-[11px] font-bold text-primary-900 dark:bg-primary-900/50 dark:text-primary-100">2</span>
                                <span>{{ __('A warehouse operator counts the goods against your request and records what they found.') }}</span>
                            </li>
                            <li class="flex gap-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-100 font-mono text-[11px] font-bold text-primary-900 dark:bg-primary-900/50 dark:text-primary-100">3</span>
                                <span>{{ __('A manager approves or rejects it. Stock moves on approval, and never before.') }}</span>
                            </li>
                        </ol>
                    </div>

                    @if ($canSubmit)
                        <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                                </svg>
                                {{ __('Submit for approval') }}
                            </button>

                            <a href="{{ $indexAction }}"
                               class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                {{ __('Cancel') }}
                            </a>

                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('You will get a reference number like') }}
                                <code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-[11px] dark:bg-gray-700">{{ old('type') === 'OUT' ? 'TRX-OUT' : 'TRX-IN' }}-20261016-001</code>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
@endsection