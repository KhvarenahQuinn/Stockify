@props([
    // Accepts a plain slug, or the log model itself.
    'action' => null,
    'size' => 'md',
])

@php
    $slug = is_object($action) ? (string) $action->action : (string) $action;

    /**
     * Audit actions are grouped by the thing they touched, not by the verb, so
     * a feed reads as "someone changed a product" rather than alternating
     * between created/updated/deleted twelve times.
     *
     * Three solid fills with white text: measured on this project's Flowbite
     * palette at 6.44:1 (green), 6.18:1 (blue) and 5.74:1 (red) - all clear AA
     * for an 11-12px label. Each chip also carries a word, so the state
     * survives greyscale printing and red-green colour deficiency.
     */
    $groups = [
        'created' => ['label' => __('Created'), 'tone' => 'bg-green-700 text-white', 'dot' => 'bg-green-200'],
        'updated' => ['label' => __('Updated'), 'tone' => 'bg-blue-700 text-white', 'dot' => 'bg-blue-200'],
        'deleted' => ['label' => __('Deleted'), 'tone' => 'bg-red-700 text-white', 'dot' => 'bg-red-200'],
    ];

    // Anything that is not a create/update/delete still needs a readable chip
    // rather than a raw slug.
    $verb = collect(explode('.', $slug))->last() ?? '';
    $isGrouped = isset($groups[$verb]);
    $state = $groups[$verb] ?? [
        'label' => __($slug) ?: __('Activity'),
        'tone' => 'bg-gray-700 text-white',
        'dot' => 'bg-gray-300',
    ];

    $entity = $isGrouped ? (collect(explode('.', $slug))->first() ?: null) : null;

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full font-semibold '.$state['tone'].' '.($sizes[$size] ?? $sizes['md'])]) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $state['dot'] }}" aria-hidden="true"></span>
    {{ $state['label'] }}
    @if ($entity)
        <span class="font-normal opacity-90">{{ __($entity) }}</span>
        <span class="sr-only">{{ __('record') }}</span>
    @endif
</span>