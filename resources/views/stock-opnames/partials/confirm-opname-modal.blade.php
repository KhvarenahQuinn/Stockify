{{--
    Confirmation before a count is posted.

    This modal is driven by opname-ui.js rather than Flowbite's data-modal-toggle,
    because the sheet is built client-side and the dialog has to be populated
    from rows that do not exist until the auditor starts typing. The shared shell
    is still used for the chrome so it looks like every other dialog in the app.
--}}
<x-modal-shell id="confirm-opname-modal"
               title="{{ __('Submit this count?') }}"
               subtitle="{{ __('Check the differences before sending this count for verification.') }}"
               maxWidth="max-w-4xl">

    <x-slot:body>
        {{-- The consequence, stated plainly and before the numbers. --}}
        <div class="flex gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-400"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
            </svg>
            <p class="text-sm text-amber-900 dark:text-amber-100">
                <span class="font-semibold">{{ __('Submitting saves the count but does not change the official stock balance') }}</span>
                {{ __('A manager or admin must verify this sheet before any difference is applied to the movement ledger. The count is kept as an audit record either way.') }}
            </p>
        </div>

        {{-- The id is load-bearing: the script finds this block by id, not by a data
     attribute, when it fills the totals in. --}}
<dl id="confirm-opname-totals" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-700">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('Items') }}</dt>
                <dd class="mt-0.5 text-xl font-bold tabular-nums text-gray-900 dark:text-white"
                    data-confirm-total="items">0</dd>
            </div>
            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-700">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('Matched') }}</dt>
                <dd class="mt-0.5 text-xl font-bold tabular-nums text-green-700 dark:text-green-400"
                    data-confirm-total="matched">0</dd>
            </div>
            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-700">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('Deficit') }}</dt>
                <dd class="mt-0.5 text-xl font-bold tabular-nums text-red-700 dark:text-red-400"
                    data-confirm-total="deficit">0</dd>
            </div>
            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-700">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('Surplus') }}</dt>
                <dd class="mt-0.5 text-xl font-bold tabular-nums text-blue-700 dark:text-blue-400"
                    data-confirm-total="surplus">0</dd>
            </div>
        </dl>

        <h3 class="mt-5 text-sm font-semibold text-gray-900 dark:text-white">
            {{ __('Differences the manager will review') }}
        </h3>

        <div class="mt-2 max-h-64 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">
                    {{ __('Every counted product whose physical count differs from the system stock') }}
                </caption>
                <thead class="sticky top-0 bg-gray-50 text-xs uppercase tracking-wide text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-3 py-2">{{ __('Product') }}</th>
                        <th scope="col" class="px-3 py-2 text-right">{{ __('System') }}</th>
                        <th scope="col" class="px-3 py-2 text-right">{{ __('Physical') }}</th>
                        <th scope="col" class="px-3 py-2 text-right">{{ __('Difference') }}</th>
                        <th scope="col" class="px-3 py-2">{{ __('Reason') }}</th>
                    </tr>
                </thead>
                <tbody id="confirm-opname-breakdown" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
            </table>
        </div>

        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Products whose count matched the system figure are not listed. They remain on the sheet as evidence they were checked.') }}
        </p>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-opname-confirm-cancel data-modal-hide="confirm-opname-modal"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-400 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
            {{ __('Keep counting') }}
        </button>

        <button type="button" data-opname-confirm-submit
                class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400">
            {{ __('Submit for verification') }}
        </button>
    </x-slot:footer>
</x-modal-shell>