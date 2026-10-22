@props([
    'sheet' => null,
    'status' => null,
    'size' => 'md',
])

@php
    use App\Models\StockOpnameSheet;

    $key = $status ?? $sheet?->status ?? StockOpnameSheet::STATUS_DRAFT;

    /**
     * Same three solid fills as the variance badges, so a status chip and a
     * discrepancy chip read as one vocabulary. White text clears AA on all
     * three (green 6.44:1, blue 6.18:1, red 5.74:1).
     *
     * SUBMITTED reads as "Reconciled" rather than "Submitted" because that is
     * what submitting did: the physical count is now the official balance.
     */
    $tones = [
        StockOpnameSheet::STATUS_DRAFT => [
            'label' => __('Draft'),
            'tone' => 'bg-gray-700 text-white',
            'dot' => 'bg-gray-300',
        ],
        StockOpnameSheet::STATUS_SUBMITTED => [
            'label' => __('Reconciled'),
            'tone' => 'bg-green-700 text-white',
            'dot' => 'bg-green-200',
        ],
        StockOpnameSheet::STATUS_ADJUSTED => [
            'label' => __('Adjusted'),
            'tone' => 'bg-blue-700 text-white',
            'dot' => 'bg-blue-200',
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