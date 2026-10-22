@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Goods Receipt / Issue Report') }}</h1>
        <div class="flex gap-2">
            <x-reports.export-button route="reports.transactions.export" format="xlsx" :filters="request()->query()" />
            <x-reports.export-button route="reports.transactions.export" format="pdf" :filters="request()->query()" />
        </div>
    </div>

    <form method="GET" action="{{ route('reports.transactions') }}"
          class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="from" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Date from') }}</label>
            <input type="date" name="start_date" id="start_date" value="{{ $report['filters']['from'] ?? '' }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label for="to" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Date to') }}</label>
            <input type="date" name="end_date" id="end_date" value="{{ $report['filters']['to'] ?? '' }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label for="supplier_id" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Supplier') }}</label>
            <select name="supplier_id" id="supplier_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">{{ __('All suppliers') }}</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(($report['filters']['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="type" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Type') }}</label>
            <select name="type" id="type"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">{{ __('All types') }}</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(($report['filters']['type'] ?? '') === $type)>
                        {{ __($type === 'IN' ? 'Goods Receipt' : 'Goods Issue') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</label>
            <select name="status" id="status"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">{{ __('All statuses') }}</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(($report['filters']['status'] ?? '') === $status)>{{ __(ucfirst(strtolower($status))) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="category_id" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Category') }}</label>
            <select name="category_id" id="category_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">{{ __('All categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(($report['filters']['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="search" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Search product') }}</label>
            <input type="text" name="search" id="search" value="{{ $report['filters']['search'] ?? '' }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 dark:bg-primary-600">{{ __('Apply') }}</button>
            <a href="{{ route('reports.transactions') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Reset') }}</a>
        </div>
    </form>

    @include('reports.partials.totals', ['items' => [
        ['label' => __('Transactions'), 'value' => $report['totals']['transactions']],
        ['label' => __('Received (qty)'), 'value' => $report['totals']['in_quantity']],
        ['label' => __('Issued (qty)'), 'value' => $report['totals']['out_quantity']],
        ['label' => __('Pending'), 'value' => $report['totals']['pending']],
        ['label' => __('Rejected'), 'value' => $report['totals']['rejected']],
    ]])

    <div class="mt-6 relative overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">{{ __('Reference') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Type') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Product') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Supplier') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Qty') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Stock') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $transaction)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ $transaction->reference_number }}</th>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded {{ $transaction->type === 'IN' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-400' }}">{{ $transaction->type }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $transaction->product?->name ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $transaction->supplier?->name ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $transaction->quantity }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($transaction->status === 'PENDING')
                                <span class="text-gray-400">—</span>
                            @else
                                {{ $transaction->stock_before }} &rarr; {{ $transaction->stock_after }}
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ __(ucfirst(strtolower($transaction->status))) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->created_at?->translatedFormat('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-4 text-center">{{ __('No transactions match these filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection