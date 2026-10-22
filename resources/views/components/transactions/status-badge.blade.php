@props([
    // A StockTransaction is the normal input, but a bare status key is accepted
    // so a filter chip or an empty-state callout can reuse the same vocabulary.
    'transaction' => null,
    'status' => null,
    'size' => 'md',
])

@php
    use App\Models\StockTransaction;

    $key = $status ?? $transaction?->displayStatus() ?? StockTransaction::STATUS_PENDING;

    /**
     * Three solid fills, white text, no colour-only signalling.
     *
     * amber-700 rather than the amber-600 the brief named: white on #D97706 is
     * 3.2:1, which clears the 3:1 floor for large text and fails the 4.5:1 that a
     * 12px label needs. #B45309 measures 5.0:1 and sits at the same visual
     * weight as the green (5.0:1) and red (6.5:1) beside it, so the three read
     * as one set rather than a warning that looks brighter than a success.
     *
     * Each chip also carries a dot and a word, so the state survives both
     * greyscale printing and the ~1 in 12 men with a red-green colour deficiency.
     */
    $tones = [
        StockTransaction::DISPLAY_PENDING_INSPECTION => [
            'label' => __('Pending inspection'),
            'tone' => 'bg-amber-700 text-white',
            'dot' => 'bg-amber-200',
        ],
        StockTransaction::DISPLAY_AWAITING_APPROVAL => [
            'label' => __('Awaiting approval'),
            'tone' => 'bg-amber-700 text-white',
            'dot' => 'bg-amber-200',
        ],
        StockTransaction::DISPLAY_DISCREPANCY => [
            'label' => __('Discrepancy'),
            'tone' => 'bg-red-700 text-white',
            'dot' => 'bg-red-200',
        ],
        StockTransaction::STATUS_APPROVED => [
            'label' => __('Approved'),
            'tone' => 'bg-green-700 text-white',
            'dot' => 'bg-green-200',
        ],
        StockTransaction::STATUS_COMPLETED => [
            'label' => __('Completed'),
            'tone' => 'bg-green-700 text-white',
            'dot' => 'bg-green-200',
        ],
        StockTransaction::STATUS_REJECTED => [
            'label' => __('Rejected'),
            'tone' => 'bg-red-700 text-white',
            'dot' => 'bg-red-200',
        ],
    ];

    $fallback = [
        'label' => str_replace('_', ' ', strtolower($key)),
        'tone' => 'bg-gray-700 text-white',
        'dot' => 'bg-gray-300',
    ];

    $state = $tones[$key] ?? $fallback;

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full font-semibold '.$state['tone'].' '.($sizes[$size] ?? $sizes['md'])]) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $state['dot'] }}" aria-hidden="true"></span>
    {{ $state['label'] }}
</span>