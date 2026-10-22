@props(['items'])

<div class="col-span-2 lg:col-span-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    @foreach($items as $item)
        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __($item['label']) }}</p>
            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $item['value'] }}</p>
        </div>
    @endforeach
</div>