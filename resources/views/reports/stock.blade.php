@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Stock Report') }}</h1>
        <div class="flex gap-2">
            <x-reports.export-button route="reports.stock.export" format="xlsx" :filters="request()->query()" />
            <x-reports.export-button route="reports.stock.export" format="pdf" :filters="request()->query()" />
        </div>
    </div>

    <form method="GET" action="{{ route('reports.stock') }}"
          class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-5">
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
            <label for="stock_status" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Stock status') }}</label>
            <select name="stock_status" id="stock_status"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                @foreach($stockStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(($report['filters']['stock_status'] ?? 'all') === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>

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

        <div class="sm:col-span-2 lg:col-span-4">
            <label for="search" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Search') }}</label>
            <input type="text" name="search" id="search" value="{{ $report['filters']['search'] ?? '' }}"
                   placeholder="{{ __('Product name or SKU') }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-primary-700 rounded-lg hover:bg-primary-800 dark:bg-primary-600">{{ __('Apply') }}</button>
            <a href="{{ route('reports.stock') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Reset') }}</a>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-4 mb-6 lg:grid-cols-5">
        @include('reports.partials.totals', ['items' => [
            ['label' => __('Products'), 'value' => $report['totals']['products']],
            ['label' => __('Units on hand'), 'value' => $report['totals']['units_on_hand']],
            ['label' => __('Stock value'), 'value' => 'Rp '.format_number((float) $report['totals']['stock_value'], 0, ',', '.')],
            ['label' => __('Low stock'), 'value' => $report['totals']['low_stock']],
            ['label' => __('Out of stock'), 'value' => $report['totals']['out_of_stock']],
        ]])
    </div>

    <div class="relative overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">{{ __('SKU') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Product') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Category') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Supplier') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('On Hand') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('Min') }}</th>
                    @if(isset($report['filters']['from']) || isset($report['filters']['to']))
                        <th scope="col" class="px-6 py-3">{{ __('Period In') }}</th>
                        <th scope="col" class="px-6 py-3">{{ __('Period Out') }}</th>
                    @endif
                    <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $product)
                    @php
                        $statusKey = $product->current_stock === 0
                            ? 'Out of stock'
                            : ($product->current_stock <= $product->min_stock ? 'Low stock' : 'Safe');
                        $status = __($statusKey);
                        $tone = match ($statusKey) {
                            'Out of stock' => 'bg-red-700 text-white',
                            'Low stock' => 'bg-amber-700 text-white',
                            default => 'bg-green-700 text-white',
                        };
                    @endphp
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4">{{ $product->sku }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $product->name }}</td>
                        <td class="px-6 py-4">{{ $product->category?->name ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $product->supplier?->name ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $product->current_stock }}</td>
                        <td class="px-6 py-4">{{ $product->min_stock }}</td>
                        @if(isset($report['filters']['from']) || isset($report['filters']['to']))
                            <td class="px-6 py-4">{{ (int) ($product->period_in ?? 0) }}</td>
                            <td class="px-6 py-4">{{ (int) ($product->period_out ?? 0) }}</td>
                        @endif
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded {{ $tone }}">{{ $status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-4 text-center">{{ __('No products match these filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection