@php
    $modalId = 'view-product-'.$product->id;
    $imageUrl = $product->image_url;
    $margin = (float) $product->sell_price - (float) $product->buy_price;
@endphp

<x-modal-shell :id="$modalId"
               title="{{ $product->name }}"
               subtitle="{{ $product->sku }}"
               max-width="max-w-2xl">
    <x-slot:body>
        <div class="flex flex-col gap-5 sm:flex-row">
            <div class="h-40 w-40 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-600 dark:bg-gray-700">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center">
                        <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                        </svg>
                    </div>
                @endif
            </div>

            <dl class="grid flex-1 grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Category') }}</dt>
                    <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">{{ $product->category?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Supplier') }}</dt>
                    <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">{{ $product->supplier?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Buy price') }}</dt>
                    <dd class="mt-0.5 font-mono tabular-nums text-gray-900 dark:text-white">{{ 'Rp '.format_number((float) $product->buy_price, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Sell price') }}</dt>
                    <dd class="mt-0.5 font-mono tabular-nums text-gray-900 dark:text-white">{{ 'Rp '.format_number((float) $product->sell_price, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Margin') }}</dt>
                    <dd class="mt-0.5 font-mono tabular-nums {{ $margin >= 0 ? 'text-green-800 dark:text-green-300' : 'text-red-700 dark:text-red-400' }}">
                        {{ 'Rp '.format_number($margin, 0, ',', '.') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('On hand') }}</dt>
                    <dd class="mt-0.5 flex flex-col items-start gap-1 font-mono tabular-nums text-gray-900 dark:text-white">
                        <span>{{ format_number($product->current_stock) }} {{ $product->unit }}</span>
                        <x-master.stock-badge :stock="$product->current_stock" :min-stock="$product->min_stock" />
                    </dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Reorder point') }}</dt>
                    <dd class="mt-0.5 font-mono tabular-nums text-gray-900 dark:text-white">{{ format_number($product->min_stock) }} {{ $product->unit }}</dd>
                </div>
                @if ($product->attributeValues->isNotEmpty())
                    <div class="col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Attributes') }}</dt>
                        <dd class="mt-1 flex flex-wrap gap-1.5">
                            @foreach ($product->attributeValues as $attributeValue)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $attributeValue->attribute?->name }}</span>
                                    <span class="mx-1 text-gray-400 dark:text-gray-500" aria-hidden="true">&middot;</span>
                                    <span class="font-medium">{{ $attributeValue->value }}</span>
                                </span>
                            @endforeach
                        </dd>
                    </div>
                @endif
                @if (filled($product->description))
                    <div class="col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Description') }}</dt>
                        <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $product->description }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" data-modal-hide="{{ $modalId }}"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            {{ __('Close') }}
        </button>
    </x-slot:footer>
</x-modal-shell>