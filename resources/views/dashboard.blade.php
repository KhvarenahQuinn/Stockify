@extends('layouts.app')

@section('content')
<div class="p-4">
    @php
        /**
         * Role-dispatched dashboard.
         *
         * The three views are separate files rather than three branches in one
         * template. A manager's dashboard and an admin's share only the page
         * frame - every metric, table and queue below the heading differs - so
         * keeping them in one file meant a 175-line conditional where each role
         * could only be read by scrolling past the other two.
         *
         * $dashboardView is a full view name from the controller's whitelist
         * (e.g. 'dashboard.admin'), used as-is. Prefixing it again here
         * resolved to 'dashboard.dashboard.admin'.
         */
        $view = $dashboardView;
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('Welcome,') }} {{ auth()->user()?->name }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Signed in as') }}
            <span class="font-medium text-gray-700 dark:text-gray-300">
                {{ auth()->user()?->role?->display_name ?? auth()->user()?->role?->name ?? __('no role assigned') }}
            </span>
        </p>
    </div>

    @include($view, ['metrics' => $metrics, 'chart' => $chart])
</div>
@endsection