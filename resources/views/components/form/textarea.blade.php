@props([
    'name',
    'label',
    'value' => null,
    'id' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
    'rows' => 3,
])

@php
    $fieldId = $id ?? 'field-'.$name;
    $message = $errors->first($name);
    $current = old($name, $value);

    $control = 'block w-full rounded-lg border bg-gray-50 p-2.5 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:bg-white focus:outline-none focus:ring-1 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:bg-gray-800';
    $border = $message ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600';
    $ring = $message ? 'focus:border-red-500 focus:ring-red-500' : 'focus:border-primary-500 focus:ring-primary-500';
@endphp

<div>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
        {{ $label }}
        @if ($required)
            <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
        @endif
    </label>

    <textarea
        name="{{ $name }}"
        id="{{ $fieldId }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($message) aria-invalid="true" @endif
        @if ($hint || $message) aria-describedby="{{ $message ? 'error-'.$fieldId : 'hint-'.$fieldId }}" @endif
        class="{{ trim("{$control} {$border} {$ring}") }}"
        @foreach ($attributes->except(['class', 'style']) as $key => $value) {{ $key }}="{{ $value }}" @endforeach
    >{{ $current }}</textarea>

    @if ($message)
        <p id="error-{{ $fieldId }}" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @elseif ($hint)
        <p id="hint-{{ $fieldId }}" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>