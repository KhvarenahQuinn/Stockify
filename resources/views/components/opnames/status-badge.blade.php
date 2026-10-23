@props([
    'sheet' => null,
    'status' => null,
    'size' => 'md',
])

@php
    use App\Models\StockOpnameSheet;

    $key = $status ?? $sheet?->status ?? StockOpnameSheet::STATUS_DRAFT;

    /**
     * Same solid fills as the variance badges, so a status chip and a
     * discrepancy chip read as one vocabulary. White text clears AA on all
     * three (gray 10.3:1, amber 5.02:1, green 5.02:1).
     *
     * SUBMITTED is deliberately amber and says "Awaiting verification":
     * submission saves the count but does not move the official balance.
     */
    $tones = [
        StockOpnameSheet::STATUS_DRAFT => [
            'label' => __('Draft'),
            'tone' => 'bg-gray-700 text-white',
            'dot' => 'bg-gray-300',
        ],
        StockOpnameSheet::STATUS_SUBMITTED => [
            'label' => __('Awaiting verification'),
            'tone' => 'bg-amber-700 text-white',
            'dot' => 'bg-amber-200',
        ],
        StockOpnameSheet::STATUS_ADJUSTED => [
            'label' => __('Applied'),
            'tone' => 'bg-green-700 text-white',
            'dot' => 'bg-green-200',
        ],
    ];

    $state = $tones[$key] ?? [
        'label' => str_replace('_', ' ', strtolower($key)),
        'tone' => 'bg-gray-700 text-white',
        'dot' => 'bg-gray-300',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full font-semibold '.$state['tone'].' '.($sizes[$size] ?? $sizes['md'])]) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $state['dot'] }}" aria-hidden="true"></span>
    {{ $state['label'] }}
</span>