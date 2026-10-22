{{--
    A role with no dashboard of its own.

    This exists so the controller never has to point an unknown role at another
    role's template. guestMetrics() returns only ['role' => null], so handing it
    the staff view produced "Undefined array key: pending_incoming" instead of a
    page. The role list is data, and data changes.
--}}

<section class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
        {{ __('No dashboard is set up for your role') }}
    </h2>

    <p class="mt-2 max-w-prose text-sm text-gray-600 dark:text-gray-300">
        {{ __('Your account is signed in as') }}
        <span class="font-medium text-gray-900 dark:text-white">
            {{ auth()->user()?->role?->display_name ?? __('no role assigned') }}
        </span>,
        {{ __('and that role has no overview yet. An administrator can assign a different role from user management.') }}
    </p>

    @if (Route::has('products.index'))
        <a href="{{ route('products.index') }}"
           class="mt-4 inline-flex rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400">
            {{ __('Browse products') }}
        </a>
    @endif
</section>