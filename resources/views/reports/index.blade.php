@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Reports') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Filter a report, check the figures on screen, then download it.') }}
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($reports as $report)
            <a href="{{ route($report['route']) }}"
               class="flex flex-col rounded-lg border border-gray-200 bg-white p-5 transition hover:border-primary-400 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-400 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-500">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $report['icon'] }}"/>
                    </svg>
                </span>

                <span class="mt-3 text-base font-semibold text-gray-900 dark:text-white">
                    {{ __($report['title']) }}
                </span>

                <span class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __($report['description']) }}
                </span>
            </a>
        @endforeach
    </div>

    <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
        {{ __('Every report can be downloaded as a PDF or a spreadsheet, using whichever filters are currently applied.') }}
    </p>
</div>
@endsection