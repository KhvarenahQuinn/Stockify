@php
    $modalId = 'delete-user-'.$user->id;

    // The ledger's foreign keys are RESTRICT, so an account named on stock
    // records cannot be removed at all. The dialog says so up front instead of
    // letting the admin confirm a delete that the database will refuse.
    $references = $references ?? ['transactions' => 0, 'approvals' => 0, 'opnames' => 0, 'sheets' => 0];
    $blocked = array_sum($references) > 0;

    $named = [];
    foreach ([
        'transactions' => ['stock movement', 'stock movements'],
        'approvals' => ['approved movement', 'approved movements'],
        'opnames' => ['stock count', 'stock counts'],
        'sheets' => ['count sheet', 'count sheets'],
    ] as $key => [$singular, $plural]) {
        if (($references[$key] ?? 0) > 0) {
            $named[] = format_number($references[$key]).' '.__(($references[$key] === 1) ? $singular : $plural);
        }
    }

    $toggleFormId = $toggleFormId ?? null;
@endphp

<x-modal-shell :id="$modalId"
               title="{{ __('Delete user') }}"
               max-width="max-w-lg"
               :form-action="$blocked ? null : route('admin.users.destroy', $user)"
               :form-method="$blocked ? null : 'DELETE'">
    <x-slot:body>
        <div class="space-y-5">
            {{-- Which account: name, sign-in address, role and state, so the
                 admin can confirm the row they clicked on. --}}
            <div class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-700 text-sm font-semibold text-white">
                    {{ strtoupper(mb_substr((string) $user->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900/40 dark:text-primary-200">
                            {{ $user->role?->display_name ?? $user->role?->name ?? __('Unassigned') }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->status === 'active'
                            ? 'bg-green-700 text-white'
                            : 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200' }}">
                            {{ __($user->status === 'active' ? 'Active' : 'Inactive') }}
                        </span>
                    </div>
                </div>
            </div>

            @if ($blocked)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/40">
                    <p class="flex items-center gap-2 text-sm font-semibold text-red-800 dark:text-red-200">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        {{ __('Delete is refused') }}
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-red-800 dark:text-red-200">
                        {{ __(':records still name this account. The inventory ledger has to keep its author, so the account can only be deactivated.', ['records' => implode(', ', $named)]) }}
                    </p>
                </div>
            @else
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                    {{ __('Deleting :name ends their access for good. This cannot be undone.', ['name' => $user->name]) }}
                </p>
            @endif

            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex items-start gap-2">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    <span>{{ __('Signs them out immediately; their next request is rejected.') }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                    <span>{{ __('The activity log keeps their name on everything they did.') }}</span>
                </li>
                @unless ($blocked)
                    <li class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        <span>{{ __('Nothing in the inventory ledger points at this account, so no stock history is lost.') }}</span>
                    </li>
                @endunless
            </ul>
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="{{ $modalId }}"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ $blocked ? __('Close') : __('Cancel') }}
        </button>

        @if ($blocked)
            @if ($toggleFormId)
                {{-- The safe alternative to a refused delete. The button submits
                     the status form already on the page through its `form`
                     attribute, so no second copy of that form is needed. --}}
                <button type="submit" form="{{ $toggleFormId }}"
                        class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                    {{ __($user->status === 'active' ? 'Deactivate user' : 'Activate user') }}
                </button>
            @endif
        @else
            <button type="submit"
                    class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800">
                {{ __('Delete user') }}
            </button>
        @endif
    </x-slot:footer>
</x-modal-shell>
