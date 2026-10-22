@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'id' => null,
    'placeholder' => null,
    'hint' => null,
    'prefix' => null,
    // Left padding that clears the prefix. A fixed pl-8 only fits one- or
    // two-character prefixes; a path prefix like "/categories/" needs the
    // caller to say so, or the typed value renders underneath it.
    'prefixPadding' => 'pl-8',
    'required' => false,
    'readonly' => false,
    'autocomplete' => null,
    'inputmode' => null,
    'rows' => null,
])

@php
    $fieldId = $id ?? 'field-'.$name;
    $message = $errors->first($name);
    $current = old($name, $value);

    $control = 'block w-full rounded-lg border p-2.5 text-sm transition-colors focus:outline-none focus:ring-1';
    $surface = $readonly
        ? 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-500 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400'
        : 'bg-gray-50 text-gray-900 placeholder-gray-400 focus:bg-white dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:bg-gray-800';

    if ($readonly) {
        $border = $message ? 'border-red-500' : 'border-gray-200 dark:border-gray-700';
        $ring = '';
    } else {
        $border = $message ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600';
        $ring = $message ? 'focus:border-red-500 focus:ring-red-500' : 'focus:border-primary-500 focus:ring-primary-500';
    }

    $classes = trim("{$control} {$surface} {$border} {$ring}".($prefix ? ' '.$prefixPadding : ''));
@endphp

<div>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
        {{ $label }}
        @if ($required)
            <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="relative">
        @if ($prefix)
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400 dark:text-gray-500" aria-hidden="true">{{ $prefix }}</span>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $fieldId }}"
            value="{{ $current }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required @endif
            @if ($readonly) readonly @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
            @if ($message) aria-invalid="true" @endif
            @if ($hint || $message) aria-describedby="{{ $message ? 'error-'.$fieldId : 'hint-'.$fieldId }}" @endif
            class="{{ $classes }}"
            @foreach ($attributes->except(['class', 'style']) as $key => $value) {{ $key }}="{{ $value }}" @endforeach
        >
    </div>

    @if ($message)
        <p id="error-{{ $fieldId }}" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @elseif ($hint)
        <p id="hint-{{ $fieldId }}" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>