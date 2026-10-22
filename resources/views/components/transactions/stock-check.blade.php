@props([
    // 'IN' or 'OUT'. Both the radio group and this panel read the same
    // [data-movement-type] inputs, so there is no state to fall out of sync.
    'type' => 'IN',
])

{{--
    The availability read-out for the movement form.

    Static markup only. Every number inside is replaced at runtime by
    transaction-ui.js from the same product the combobox posted, which is why
    the placeholders read as an empty state rather than as data.

    The two callouts keep `flex` and gain `hidden` at runtime. They cannot use
    the HTML hidden attribute instead: Preflight's `[hidden]` rule sits in the
    base layer and `flex` in the utilities layer above it, so the attribute
    would lose and the callouts would never collapse. Within the utilities layer
    `hidden` is emitted after `flex`, so the class is the one that works.
--}}
<div data-stock-check
     class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700/50"
     aria-live="polite">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ __('Availability') }}
        </p>

        <p class="text-xs text-gray-500 dark:text-gray-400" data-stock-direction>
            {{ __($type === 'IN' ? 'This will increase stock on hand.' : 'This will reduce stock on hand.') }}
        </p>
    </div>

    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" data-stock-empty>
        {{ __('Pick a product to see how much is on hand.') }}
    </p>

    <div data-stock-figures class="mt-2 hidden">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <span class="min-w-0 truncate text-sm font-medium text-gray-900 dark:text-white" data-stock-name></span>
            <code class="shrink-0 rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] text-gray-600 dark:bg-gray-600 dark:text-gray-300" data-stock-sku></code>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-2 font-mono text-sm tabular-nums">
            <span class="rounded bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                <span class="font-sans text-[11px] text-gray-500 dark:text-gray-400">{{ __('On hand') }}</span>
                <span class="ml-1 font-semibold" data-stock-available>0</span>
            </span>

            <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">&rarr;</span>

            <span class="rounded bg-gray-200 px-2 py-1 font-semibold text-gray-900 dark:bg-gray-500 dark:text-white">
                <span class="font-sans text-[11px] font-normal text-gray-600 dark:text-gray-300">{{ __('After') }}</span>
                <span class="ml-1" data-stock-projected>0</span>
            </span>

            <span class="text-xs font-medium" data-stock-delta></span>
        </div>
    </div>

    {{-- Shown only when an OUT request is larger than what the shelf holds.
         Red at 700 with white text: same scale as the status badges, so the
         warning reads with the same authority as the state it is correcting. --}}
    <p data-stock-warning
       role="alert"
       class="mt-3 hidden items-start gap-2 rounded-lg bg-red-700 px-3 py-2 text-sm text-white">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <span data-stock-warning-text></span>
    </p>

    <p data-stock-note
       class="mt-2 hidden items-start gap-2 text-xs text-gray-500 dark:text-gray-400">
        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span data-stock-note-text></span>
    </p>
</div>