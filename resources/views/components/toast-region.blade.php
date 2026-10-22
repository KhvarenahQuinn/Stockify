{{--
    The global toast region.

    Every flash message in the application is rendered here as a toast, so a
    form that flashes 'success' gets feedback without the view having to know
    a toast system exists. The views no longer print their own flash blocks -
    that is what stops the same sentence appearing twice.

    The region is last in the body, so at the same z-index as a Flowbite modal
    it still paints above it.
--}}

<div data-toast-region
     class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-xs flex-col gap-3 sm:right-5 sm:top-5"
     aria-live="polite"
     aria-atomic="false">

    @if (session('success'))
        <x-toast type="success" :message="session('success')" />
    @endif

    @if (session('error'))
        <x-toast type="error" :message="session('error')" />
    @endif

    @if (session('warning'))
        <x-toast type="warning" :message="session('warning')" />
    @endif

    @if (session('info'))
        <x-toast type="info" :message="session('info')" />
    @endif

    {{--
        A validation failure is reported once, as a toast. The per-field
        messages stay inline under their inputs - those are not a duplicate,
        they say which field is wrong.
    --}}
    @if ($errors->any())
        <x-toast type="error" :message="$errors->first()" />
    @endif
</div>

{{--
    One template per tone, rendered by the same component that renders a real
    toast. The script clones the one it needs and writes the message, so the
    tone classes live in Blade only - there is no palette in JavaScript to
    drift from the one the server renders.
--}}
@foreach (['success', 'error', 'warning', 'info'] as $tone)
    <template data-toast-template="{{ $tone }}">
        <x-toast :type="$tone" :message="''" />
    </template>
@endforeach
