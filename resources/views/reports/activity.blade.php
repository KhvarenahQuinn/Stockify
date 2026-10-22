@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('User Activity Log') }}</h1>
        <div class="flex gap-2">
            <x-reports.export-button route="reports.activity.export" format="xlsx" :filters="request()->query()" />
            <x-reports.export-button route="reports.activity.export" format="pdf" :filters="request()->query()" />
        </div>
    </div>

    @if(! auth()->user()?->can('viewAllActivity', App\Models\ActivityLog::class))
        <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-100 dark:bg-blue-900 dark:text-blue-300">
            {{ __('You are seeing your own activity only. Ask a manager or admin for the full audit trail.') }}
        </div>
    @endif

    <form method="GET" action="{{ route('reports.activity') }}"
          class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="from" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Date from') }}</label>
            <input type="date" name="from" id="from" value="{{ $report['filters']['from'] ?? '' }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label for="to" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Date to') }}</label>
            <input type="date" name="to" id="to" value="{{ $report['filters']['to'] ?? '' }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label for="user_id" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('User') }}</label>
            <select name="user_id" id="user_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">{{ __('All users') }}</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(($report['filters']['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="action" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Action') }}</label>
            <select name="action" id="action"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">{{ __('All actions') }}</option>
                @foreach($actionPrefixes as $prefix)
                    <option value="{{ __($prefix) }}" @selected(($report['filters']['action'] ?? '') === $prefix)>{{ __($prefix) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 dark:bg-primary-600">{{ __('Apply') }}</button>
            <a href="{{ route('reports.activity') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Reset') }}</a>
        </div>
    </form>

    @include('reports.partials.totals', ['items' => [
        ['label' => __('Log entries'), 'value' => $report['totals']['entries']],
    ]])

    <div class="mt-6 relative overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">{{ __('Date') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('User') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Action') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Description') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('IP') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $log)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $log->user?->name ?? '—' }}</td>
                        <td class="px-6 py-4"><code class="text-xs">{{ $log->displayAction() }}</code></td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $log->description }}</td>
                        <td class="px-6 py-4">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-4 text-center">{{ __('No activity matches these filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection