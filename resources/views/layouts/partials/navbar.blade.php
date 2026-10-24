{{--
    layouts.app is shared by the authenticated screens and by the public
    practice pages, so everything that touches the user has to sit behind an
    @auth guard. Guests fall through to the sign-in link below.
--}}
@auth
    @php
        $user = auth()->user();
        $initials = mb_strtoupper(collect(explode(' ', trim($user->name)))
            ->filter()
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode(''));
    @endphp
@endauth

<header class="fixed inset-x-0 top-0 z-50 border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <nav class="flex items-center justify-between gap-3 px-3 py-3 lg:px-5" aria-label="{{ __('Top navigation') }}">
        <div class="flex items-center gap-2">
            <button type="button"
                    data-drawer-target="drawer-navigation"
                    data-drawer-toggle="drawer-navigation"
                    aria-controls="drawer-navigation"
                    aria-label="{{ __('Open navigation') }}"
                    class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 lg:hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <button id="sidebar-toggle" type="button"
                    data-collapse-label="{{ __('Collapse sidebar') }}"
                    data-expand-label="{{ __('Expand sidebar') }}"
                    aria-controls="drawer-navigation"
                    aria-expanded="true"
                    aria-label="{{ __('Collapse sidebar') }}"
                    class="hidden rounded-lg p-2 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 lg:inline-flex dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                <svg class="sidebar-toggle-icon h-5 w-5 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 4.5 11.25 12l7.5 7.5M11.25 4.5 3.75 12l7.5 7.5"/>
                </svg>
            </button>

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600" aria-hidden="true">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </span>
                <span class="whitespace-nowrap text-xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Stockify') }}</span>
            </a>
        </div>

        <div class="flex items-center gap-2">
            <button id="theme-toggle" type="button"
                    class="rounded-lg p-2.5 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                <span class="sr-only">{{ __('Toggle dark mode') }}</span>
                <svg id="theme-toggle-dark-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                </svg>
                <svg id="theme-toggle-light-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.706-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
                          clip-rule="evenodd"/>
                </svg>
            </button>

            @auth
                <button id="user-menu-button" type="button"
                        data-dropdown-toggle="dropdown-user"
                        aria-expanded="false"
                        class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition-colors hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-gray-700 dark:text-primary-400">
                        {{ $initials }}
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-medium leading-tight text-gray-900 dark:text-white">{{ $user->name }}</span>
                        <span class="block text-xs font-medium leading-tight text-gray-500 dark:text-gray-400">{{ $user->role?->display_name }}</span>
                    </span>
                    <svg class="h-3 w-3 shrink-0 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 10 6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <div id="dropdown-user" class="z-50 hidden w-64 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                    <div class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        <span class="mt-2 inline-flex items-center rounded-sm bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                            {{ $user->role?->display_name ?? __('No role') }}
                        </span>
                    </div>

                    <div class="py-2">
                        @if (Route::has('preferences.edit'))
                            <a href="{{ route('preferences.edit') }}"
                               class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">
                                {{ __('Language') }}
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-600">
                                {{ __('Sign out') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endauth

            @guest
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                    </svg>
                    {{ __('Log in') }}
                </a>
            @endguest
        </div>
    </nav>
</header>
