@props([
    'name',
    'label',
    'currentUrl' => null,
    'id' => null,
    'hint' => null,
    'accept' => 'image/jpeg,image/png,image/webp',
    'maxKilobytes' => 2048,
    // The name of the "remove" checkbox. The product image calls it
    // remove_image and the application logo calls it remove_logo, and the
    // controller reads the name it posted.
    'removeName' => 'remove_image',
])

@php
    $fieldId = $id ?? 'field-'.$name;
    $message = $errors->first($name);
@endphp

{{--
    The preview is rendered server-side from the stored file and swapped for a
    FileReader data URL on selection. The remove checkbox is deliberately a
    separate control from the file input: browsers cannot clear a file input by
    assignment, so "replace" and "remove" have to be two different gestures.
--}}
<div data-image-field>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
        {{ $label }}
    </label>

    <div class="flex flex-wrap items-start gap-4">
        <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-lg border {{ $message ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }} bg-gray-100 dark:bg-gray-700">
            @if ($currentUrl)
                <img data-image-preview
                     src="{{ $currentUrl }}"
                     alt=""
                     class="h-full w-full object-cover">
            @else
                <img data-image-preview src="" alt="" class="hidden h-full w-full object-cover">
                <span data-image-placeholder
                      class="flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-500">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                </span>
            @endif
        </div>

        <div class="min-w-[12rem] flex-1">
            <input type="file"
                   name="{{ $name }}"
                   id="{{ $fieldId }}"
                   accept="{{ $accept }}"
                   data-image-input
                   @if ($message) aria-invalid="true" aria-describedby="error-{{ $fieldId }}" @endif
                   class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 file:mr-3 file:cursor-pointer file:border-0 file:bg-gray-200 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-300 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:file:bg-gray-600 dark:file:text-gray-200">

            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                {{ __('JPEG, PNG or WebP, up to :size MB. Leave empty to keep the current image.', ['size' => format_number($maxKilobytes / 1024, 0)]) }}
            </p>

            @if ($currentUrl)
                <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300" data-image-remove-control>
                    <input type="checkbox" name="{{ $removeName }}" value="1" data-image-remove
                           class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                    {{ __('Remove the current image') }}
                </label>
            @endif
        </div>
    </div>

    @if ($message)
        <p id="error-{{ $fieldId }}" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    <p data-image-message class="mt-1.5 hidden text-sm text-red-600 dark:text-red-400" role="alert"></p>
</div>