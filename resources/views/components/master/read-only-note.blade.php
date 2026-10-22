{{--
    Shown in place of the Create/Edit/Delete controls when the signed-in role is
    not allowed to change master data. Explaining the absence beats silently
    dropping the buttons, which reads as a broken page.
--}}
@props(['feature'])

<p class="inline-flex items-start gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-300">
    <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span>
        <span class="font-medium text-gray-900 dark:text-white">{{ __('Read-only.') }}</span>
        {{ __('Your role can review :feature but cannot change them.', ['feature' => __($feature)]) }}
    </span>
</p>