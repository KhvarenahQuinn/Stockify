@props([
    'name',
    'label',
    'options',
    'value' => null,
    'id' => null,
    'placeholder' => __('Select one'),
    'hint' => null,
    'required' => false,
    'blank' => true,
])

@php
    $fieldId = $id ?? 'field-'.$name;
    $message = $errors->first($name);
    $current = (string) old($name, $value);

    $control = 'block w-full rounded-lg border bg-gray-50 p-2.5 text-sm transition-colors focus:outline-none focus:ring-1 dark:bg-gray-700 dark:text-white dark:focus:bg-gray-800';
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

    <select
        name="{{ $name }}"
        id="{{ $fieldId }}"
        @if ($required) required @endif
        @if ($message) aria-invalid="true" @endif
        @if ($hint || $message) aria-describedby="{{ $message ? 'error-'.$fieldId : 'hint-'.$fieldId }}" @endif
        class="{{ trim("{$control} {$border} {$ring}") }}"
        @foreach ($attributes->except(['class', 'style']) as $key => $value) {{ $key }}="{{ $value }}" @endforeach
    >
        @if ($blank)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $option)
            @php
                // Accepts either plain values, [value => label] pairs, or
                // Eloquent models with id/name, so callers can pass whatever
                // they already hold without reshaping it first.
                $optionValue = is_array($option)
                    ? ($option['value'] ?? $option['id'] ?? '')
                    : (is_object($option) ? ($option->id ?? '') : $option);
                $optionLabel = is_array($option)
                    ? ($option['label'] ?? $option['name'] ?? $optionValue)
                    : (is_object($option) ? ($option->name ?? $option->code ?? $optionValue) : $option);
            @endphp
            <option value="{{ $optionValue }}" @selected((string) $optionValue === $current)>{{ __($optionLabel) }}</option>
        @endforeach
    </select>

    @if ($message)
        <p id="error-{{ $fieldId }}" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @elseif ($hint)
        <p id="hint-{{ $fieldId }}" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>