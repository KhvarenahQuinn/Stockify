@extends('layouts.app')

@section('content')
@php
    use App\Models\StockOpnameSheet;

    $varianceFilter = $filters['variance_status'] ?? '';
@endphp

<div class="p-4">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Stock Opname History') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Every physical count, its verification status, and the adjustment it applied.') }}
            </p>
        </div>

        @if (Route::has('stock-opnames.create'))
            <a href="{{ route('stock-opnames.create') }}"
               class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400">
                {{ __('New count') }}
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('stock-opnames.index') }}"
          class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="from" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('From') }}</label>
            <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}"
                   class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>

        <div>
            <label for="to" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('To') }}</label>
            <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}"
                   class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>

        <div>
            <label for="auditor_id" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Auditor') }}</label>
            <select name="auditor_id" id="auditor_id"
                    class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('Anyone') }}</option>
                @foreach ($auditors as $auditor)
                    <option value="{{ $auditor->id }}" @selected((string) ($filters['auditor_id'] ?? '') === (string) $auditor->id)>
                        {{ $auditor->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="variance_status" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                {{ __('Variance') }}
            </label>
            <select name="variance_status" id="variance_status"
                    class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('All') }}</option>
                <option value="matched" @selected($varianceFilter === 'matched')>{{ __('Matched only') }}</option>
                <option value="discrepancy" @selected($varianceFilter === 'discrepancy')>{{ __('Discrepancy only') }}</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit"
                    class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400">
                {{ __('Filter') }}
            </button>
            <a href="{{ route('stock-opnames.index') }}"
               class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                {{ __('Reset') }}
            </a>
        </div>
    </form>

    <div class="relative overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <caption class="sr-only">
                {{ __('Stock opnames with reference, date, auditor, item count, net variance, verification status and available actions') }}
            </caption>
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th scope="col" class="px-6 py-3">{{ __('Reference') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Audit date') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Auditor') }}</th>
                    <th scope="col" class="px-6 py-3 text-right">{{ __('Items') }}</th>
                    <th scope="col" class="px-6 py-3 text-right">{{ __('Net variance') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                    <th scope="col" class="px-6 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($sheets as $sheet)
                    @php
                        $net = $sheet->totalNetVariance();
                        // A signed number, not a chip: the word inside the row
                        // badges describes one line, and "found 4" next to a net
                        // figure reads as a per-sheet result rather than a net.
                        $netTone = $net > 0
                            ? 'text-blue-700 dark:text-blue-400'
                            : ($net < 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-600 dark:text-gray-300');

                        $canVerify = $sheet->isAwaitingVerification()
                            && (auth()->user()?->can('verify', $sheet) ?? false)
                            && Route::has('stock-opnames.verify');
                    @endphp

                    <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-700">
                        <th scope="row" class="whitespace-nowrap px-6 py-4 font-mono text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $sheet->reference }}
                        </th>

                        <td class="whitespace-nowrap px-6 py-4">
                            {{ $sheet->audit_date?->translatedFormat('d M Y') }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="block text-gray-900 dark:text-white">{{ $sheet->auditor?->name ?? '—' }}</span>
                        </td>

                        <td class="px-6 py-4 text-right tabular-nums">
                            {{ format_number($sheet->totalItems()) }}
                        </td>

                        <td class="px-6 py-4 text-right">
                            <span class="font-semibold tabular-nums {{ $netTone }}">
                                {{ $net > 0 ? '+' : '' }}{{ format_number($net) }}
                            </span>
                            <span class="sr-only">
                                {{ $net === 0 ? __('units, everything matched') : __('units, net difference') }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <x-opnames.status-badge :sheet="$sheet" size="sm" />
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($canVerify)
                                    <button type="button"
                                            data-modal-target="verify-opname-{{ $sheet->id }}"
                                            data-modal-toggle="verify-opname-{{ $sheet->id }}"
                                            class="rounded-lg bg-amber-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-400">
                                        {{ __('Verify & Apply Adjustment') }}
                                        <span class="sr-only">{{ __('for count') }} {{ $sheet->reference }}</span>
                                    </button>
                                @endif

                                <a href="{{ route('stock-opnames.show', $sheet) }}"
                                   class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-400 dark:border-gray-600 dark:text-white dark:hover:bg-gray-700">
                                    {{ __('View details') }}
                                    <span class="sr-only">{{ __('for count') }} {{ $sheet->reference }}</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No stock opnames recorded yet.') }}</p>
                            @if (Route::has('stock-opnames.create'))
                                <a href="{{ route('stock-opnames.create') }}"
                                   class="mt-2 inline-block text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                                    {{ __('Run the first count') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach ($sheets as $sheet)
        @if ($sheet->isAwaitingVerification()
            && (auth()->user()?->can('verify', $sheet) ?? false)
            && Route::has('stock-opnames.verify'))
            <x-opnames.verify-modal :sheet="$sheet" />
        @endif
    @endforeach

    <div class="mt-4">{{ $sheets->links() }}</div>
</div>
@endsection