<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Stockify') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.i18n')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">

    <script>
        // Resolve the theme before first paint so the shell never flashes light-on-dark.
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Apply the saved desktop sidebar width before first paint as well.
        try {
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            // Storage can be unavailable; the sidebar simply starts expanded.
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-800">
    @include('layouts.partials.navbar')

    <div class="flex pt-16">
        @include('layouts.partials.sidebar')

        <div id="main-content" class="relative min-h-screen w-full overflow-y-auto bg-gray-50 transition-[margin-left] duration-300 ease-in-out lg:ml-64 dark:bg-gray-800">
            <main>
                {{--
                    The flash message is not printed here. It is rendered as a
                    toast by the toast region at the end of the body, so there is
                    one notification system rather than two saying the same
                    thing in different places.
                --}}

                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>
    </div>
    {{-- Last in the body, so at the same z-index as a Flowbite modal it still
         paints above it. --}}
    <x-toast-region />
</body>
</html>
