@extends('layouts.app')

@section('content')
    @php
        $products = $products ?? collect();
        $selectedProductId = $selectedProductId ?? null;
        $currentType = strtoupper(old('type', 'OUT'));

        $storeAction = $storeAction ?? (Route::has('stock-transactions.adjustments.store')
            ? route('stock-transactions.adjustments.store')
            : null);

        $indexAction = Route::has('stock-transactions.index')
            ? route('stock-transactions.index')
            : url()->current();

        $canSubmit = $storeAction !== null;
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                    {{ __('Manager control') }}
                </p>
                <h1 class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Stock adjustment') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Correct a stock balance with a reason. The change is written straight to the movement ledger, never to the product record alone.') }}
                </p>
            </div>

            <a href="{{ $indexAction }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                {{ __('Movement history') }}
            </a>
        </div>

        @unless ($canSubmit)
            <p class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-100">
                {{ __('Stock adjustments are switched off on this deployment.') }}
            </p>
        @endunless

        <form method="POST" action="{{ $storeAction ?? '#' }}" data-movement-form>
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-8">
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-700/40">
                            <svg class="h-5 w-5 text-amber-700 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.34 3.94l-7.5 13a2 2 0 001.73 3h15a2 2 0 001.73-3l-7.5-13a2 2 0 00-3.46 0z"/>
                            </svg>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Correction details') }}</h2>
                        </div>

                        <div class="space-y-5 p-4 sm:p-5">
                            <fieldset>
                                <legend class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('Direction') }} <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                </legend>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @foreach (['IN' => __('Increase stock'), 'OUT' => __('Decrease stock')] as $value => $label)
                                        @php
                                            $description = $value === 'IN'
                                                ? __('Add units found on the shelf that the ledger does not know about.')
                                                : __('Remove units the ledger still carries but the shelf no longer has.');
                                            $isChecked = $currentType === $value;
                                        @endphp
                                        <label @class([
                                            'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors',
                                            'border-amber-500 bg-amber-50 dark:border-amber-400 dark:bg-amber-900/30' => $isChecked,
                                            'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/50' => ! $isChecked,
                                        ])>
                                            <input type="radio"
                                                   name="type"
                                                   value="{{ $value }}"
                                                   class="mt-0.5 h-4 w-4 border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700"
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

                            <x-transactions.product-combobox :products="$products" :selected-id="$selectedProductId" />

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <x-form.input name="quantity"
                                              label="{{ __('Quantity') }}"
                                              type="number"
                                              inputmode="numeric"
                                              min="1"
                                              step="1"
                                              required
                                              :value="old('quantity', 1)"
                                              hint="{{ __('Whole units, in the product’s own unit.') }}" />

                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700/50">
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ __('Posted by') }}
                                    </p>
                                    <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ auth()->user()?->name ?? __('Unknown') }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Your name is stored on the ledger row.') }}
                                    </p>
                                </div>
                            </div>

                            <x-form.textarea name="adjustment_reason"
                                             label="{{ __('Reason for adjustment') }}"
                                             required
                                             :rows="4"
                                             maxlength="500"
                                             :value="old('adjustment_reason')"
                                             placeholder="{{ __('Example: 6 cartons damaged by water in aisle B. Disposal ticket DSP-2026-0042 attached to the floor log.') }}"
                                             hint="{{ __('Required. This is the permanent explanation on the ledger row, so be specific.') }}" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4 lg:col-span-4">
                    <x-transactions.stock-check :type="$currentType" />

                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/30">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                            <svg class="h-4 w-4 text-amber-700 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.34 3.94l-7.5 13a2 2 0 001.73 3h15a2 2 0 001.73-3l-7.5-13a2 2 0 00-3.46 0z"/>
                            </svg>
                            {{ __('This posts immediately') }}
                        </h2>

                        <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                            <li class="flex gap-2">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-700 dark:bg-amber-400" aria-hidden="true"></span>
                                <span>{{ __('It does not enter the inspection or approval queues.') }}</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-700 dark:bg-amber-400" aria-hidden="true"></span>
                                <span>{{ __('It creates an immutable ledger row with the before and after balances.') }}</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-700 dark:bg-amber-400" aria-hidden="true"></span>
                                <span>{{ __('For a full physical count, use Stock Opname instead so a second person can verify it.') }}</span>
                            </li>
                        </ul>
                    </div>

                    @if ($canSubmit)
                        <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-800 focus:outline-none focus:ring-4 focus:ring-amber-300 dark:focus:ring-amber-900">
                                {{ __('Apply stock adjustment') }}
                            </button>

                            <a href="{{ $indexAction }}"
                               class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
@endsection
