{{--
    One modal serves every row: the report can list up to a thousand entries, and
    rendering a modal per row would triple the page weight for a control most
    visitors never open. The trigger buttons carry the row's values in data
    attributes; `activity-log-ui.js` copies them in before the modal opens.
--}}
<x-modal-shell id="activity-log-detail-modal"
               title="{{ __('Activity log details') }}"
               max-width="max-w-2xl">
    <x-slot:body>
        <div class="space-y-5">
            <div>
                <span data-activity-detail-action
                      class="inline-flex items-center rounded-md bg-primary-50 px-2.5 py-1 font-mono text-xs font-medium text-primary-800 dark:bg-primary-900/40 dark:text-primary-200"></span>
                <p data-activity-detail-description
                   class="mt-3 text-sm leading-relaxed text-gray-900 dark:text-white"></p>
                <div data-activity-detail-note
                     class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-100"
                     hidden>
                    <p class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008zM10.34 3.94l-8.4 14.55A1.5 1.5 0 003.24 21h17.52a1.5 1.5 0 001.3-2.51l-8.4-14.55a1.5 1.5 0 00-2.6 0z"/>
                        </svg>
                        <span data-activity-detail-note-text></span>
                    </p>
                </div>
            </div>

            <dl class="grid grid-cols-1 gap-4 rounded-lg border border-gray-200 p-4 text-sm sm:grid-cols-2 dark:border-gray-700">
                <div class="sm:col-span-2" data-activity-detail-subject-wrap hidden>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Affected record') }}</dt>
                    <dd data-activity-detail-subject class="mt-1 font-semibold text-gray-900 dark:text-white"></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('User') }}</dt>
                    <dd data-activity-detail-user class="mt-1 font-medium text-gray-900 dark:text-white"></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Role') }}</dt>
                    <dd data-activity-detail-role class="mt-1 text-gray-900 dark:text-white"></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Timestamp') }}</dt>
                    <dd data-activity-detail-time class="mt-1 text-gray-900 dark:text-white"></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('IP Address') }}</dt>
                    <dd data-activity-detail-ip class="mt-1 font-mono text-gray-900 dark:text-white"></dd>
                </div>
            </dl>

            <section data-activity-detail-changes hidden>
                <h4 data-activity-detail-changes-title class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">{{ __('What changed') }}</h4>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">{{ __('Values before and after this action') }}</caption>
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-3 py-2">{{ __('Field') }}</th>
                                <th scope="col" data-activity-detail-th-before class="px-3 py-2">{{ __('Before') }}</th>
                                <th scope="col" data-activity-detail-th-after class="px-3 py-2">{{ __('After') }}</th>
                            </tr>
                        </thead>
                        <tbody data-activity-detail-change-rows class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                </div>
            </section>

            <p data-activity-detail-empty
               class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-700/50 dark:text-gray-300"
               hidden>
                {{ __('This entry did not record field changes. The description above is the full trail.') }}
            </p>

            <details data-activity-detail-payload class="rounded-lg border border-gray-200 dark:border-gray-700" hidden>
                <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Raw payload') }}
                </summary>
                <pre data-activity-detail-json class="max-h-48 overflow-auto border-t border-gray-200 bg-gray-900 p-3 text-xs text-gray-100 dark:border-gray-700 dark:bg-gray-950"></pre>
            </details>
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="activity-log-detail-modal"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Close') }}
        </button>
    </x-slot:footer>
</x-modal-shell>
