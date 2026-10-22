@props([
    'stock',
    'minStock',
])

@php
    use App\Support\StockLevel;

    $state = StockLevel::describe((int) $stock, (int) $minStock);
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $state['tone'] }}">
    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-white" aria-hidden="true"></span>
    {{ $state['label'] }}
</span>