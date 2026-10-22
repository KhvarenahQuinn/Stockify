@props([
    'name' => 'document',
    'label' => __('Supporting document'),
    'currentUrl' => null,
    'currentName' => null,
    'hint' => null,
    'accept' => 'application/pdf,image/jpeg,image/png,image/webp',
    'maxKilobytes' => 5120,
])

@php
    $fieldId = $id ?? 'field-'.$name;
    $message = $errors->first($name);
@endphp

{{--
    Not a fork of form/file-upload: that one previews an image and carries a
    "remove the current image" checkbox, because a replacement image can be
    swapped out without changing anything else on the record. A delivery note is
    a link, not a picture — it is shown, named and opened, never previewed — so a
    separate component keeps each one honest instead of parameterising both into
    something neither uses well.
--}}
<div data-document-upload>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">
        {{ $label }}
        <span class="font-normal text-gray-500 dark:text-gray-400">{{ __('(optional)') }}</span>
    </label>

    <input type="file"
           name="{{ $name }}"
           id="{{ $fieldId }}"
           accept="{{ $accept }}"
           data-document-input
           {{-- The limit is handed to the client so an oversized file is refused
                before the upload starts, rather than after a full transfer and
                a 422. --}}
           data-max-kilobytes="{{ $maxKilobytes }}"
           @if ($message) aria-invalid="true" aria-describedby="error-{{ $fieldId }}" @endif
           class="block w-full cursor-pointer rounded-lg border bg-gray-50 text-sm text-gray-900 file:mr-3 file:cursor-pointer file:border-0 file:bg-gray-200 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-300 focus:outline-none focus:ring-1 dark:bg-gray-700 dark:text-white dark:file:bg-gray-600 dark:file:text-gray-200 {{ $message ? 'border-red-500 dark:border-red-500' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600' }}">

    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
        {{ $hint ?? __('The signed delivery note or packing slip. PDF or image, up to :size MB.', ['size' => format_number($maxKilobytes / 1024)]) }}
    </p>

    {{-- The file the server already holds, so a validation round-trip does not
         make the submitter re-attach a document that saved fine. --}}
    @if ($currentUrl)
        <a href="{{ $currentUrl }}" target="_blank" rel="noopener"
           class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            {{ $currentName ?? __('Attached document') }}
        </a>
    @endif

    @if ($message)
        <p id="error-{{ $fieldId }}" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @endif

    <p data-document-message class="mt-1.5 hidden text-sm text-red-600 dark:text-red-400" role="alert"></p>
</div>