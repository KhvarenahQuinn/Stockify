@php
    $user = auth()->user();
    $initials = mb_strtoupper(collect(explode(' ', trim($user->name)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode(''));
@endphp

<nav class="fixed z-50 w-full border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start">
                <button id="toggleSidebarMobile" type="button" aria-controls="sidebar" aria-expanded="false"
                        aria-label="Toggle navigation"
                        class="cursor-pointer rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 lg:hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">
                    <svg id="toggleSidebarMobileHamburger" class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <svg id="toggleSidebarMobileClose" class="hidden h-6 w-6" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>

                <a href="{{ route('dashboard') }}" class="ml-2 flex items-center gap-2 md:mr-12">
                    <img src="{{ asset('static/images/logo.svg') }}" class="h-8" alt="Stockify">
                    <span class="self-center whitespace-nowrap text-xl font-semibold text-gray-900 dark:text-white">Stockify</span>
                </a>
            </div>

            <div class="flex items-center gap-2">
                <button id="theme-toggle" type="button"
                        class="rounded-lg p-2.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                    <span class="sr-only">Toggle dark mode</span>
                    <svg id="theme-toggle-dark-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                    <svg id="theme-toggle-light-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.706-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                    </svg>
                </button>

                <button id="user-menu-button" type="button" data-dropdown-toggle="dropdown-user" aria-expanded="false"
                        class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition-colors hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-gray-700 dark:text-primary-400">
                        {{ $initials }}
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-medium leading-tight text-gray-900 dark:text-white">{{ $user->name }}</span>
                        <span class="block text-xs font-medium leading-tight text-gray-500 dark:text-gray-400">{{ $user->role?->display_name }}</span>
                    </span>
                    <svg class="h-3 w-3 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 10 6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <div id="dropdown-user" class="z-50 hidden w-64 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                    <div class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        <span class="mt-2 inline-flex items-center rounded-sm bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                            {{ $user->role?->display_name ?? 'No role' }}
                        </span>
                    </div>

                    <div class="py-2">
                        <a href="{{ route('dashboard') }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600 dark:hover:text-white">Dashboard</a>
                        <a href="{{ route('reports.activity') }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600 dark:hover:text-white">My Activity</a>
                    </div>

                    <div class="py-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-600">
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
