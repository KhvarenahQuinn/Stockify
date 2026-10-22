@props([
    'id',
    'title',
    'subtitle' => null,
    'maxWidth' => 'max-w-2xl',
    // Setting these renders the body/footer inside a real <form>, which is what
    // makes every CUD modal post through the same markup instead of a JS handler.
    'formAction' => null,
    'formMethod' => null,
    // Any modal with a file input must opt into multipart or PHP silently
    // discards the upload.
    'enctype' => null,
])

<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
     class="fixed inset-x-0 top-0 z-50 hidden h-full max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden p-4 md:inset-0">
    <div class="relative max-h-full w-full {{ $maxWidth }}">
        <div class="relative rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="flex items-start justify-between gap-4 rounded-t border-b border-gray-200 p-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                    @if ($subtitle)
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" data-modal-hide="{{ $id }}"
                        class="shrink-0 rounded-lg bg-transparent p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                    <span class="sr-only">{{ __('Close') }}</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @if ($formAction)
                <form method="POST" action="{{ $formAction }}" @if ($enctype) enctype="{{ $enctype }}" @endif>
                    @csrf
                    @if ($formMethod) @method($formMethod) @endif
            @endif

            {{-- Padding lives here rather than on each slot's grid so every modal in the
                 app gets the same inset instead of each one opting in. --}}
            <div class="max-h-[70vh] overflow-y-auto p-4 sm:p-6">
                {{ $body }}
            </div>

            @if (isset($footer))
                <div class="flex items-center justify-end gap-3 rounded-b border-t border-gray-200 p-4 dark:border-gray-700">
                    {{ $footer }}
                </div>
            @endif

            @if ($formAction)
                </form>
            @endif
        </div>
    </div>
</div>