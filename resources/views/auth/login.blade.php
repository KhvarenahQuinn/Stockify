@extends('layouts.auth')

@section('content')
    <div class="flex flex-col items-center">
        <div class="mb-6 flex items-center gap-3">
            <img src="{{ asset('static/images/stockify-logo.svg') }}" class="h-11 w-11" alt="">
            <span class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Stockify') }}</span>
        </div>

        <div class="w-full rounded-lg border border-gray-200 bg-white p-6 shadow sm:p-8 dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-5 flex items-center justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
                <span id="login-language-label" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Language') }}
                </span>

                <form method="POST" action="{{ route('locale.update') }}"
                      class="inline-flex items-center rounded-lg border border-gray-300 bg-gray-50 p-0.5 dark:border-gray-600 dark:bg-gray-700"
                      aria-labelledby="login-language-label">
                    @csrf
                    @foreach (['en' => 'EN', 'id' => 'ID'] as $locale => $label)
                        <button type="submit" name="locale" value="{{ $locale }}"
                                aria-label="{{ $locale === 'en' ? 'English' : 'Bahasa Indonesia' }}"
                                aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                                @class([
                                    'rounded-md px-2.5 py-1.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500',
                                    'bg-primary-600 text-white' => app()->getLocale() === $locale,
                                    'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600' => app()->getLocale() !== $locale,
                                ])>
                            {{ $label }}
                        </button>
                    @endforeach
                </form>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Sign in to your account') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Use the account your administrator set up for you.') }}
            </p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">{{ __('Email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           autocomplete="username" inputmode="email" required autofocus
                           placeholder="name@company.com"
                           @class([
                               'block w-full rounded-lg border p-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400',
                               'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500' => $errors->has('email'),
                               'border-gray-300 bg-gray-50 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600' => ! $errors->has('email'),
                           ])>
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-900 dark:text-white">{{ __('Password') }}</label>
                    <div class="relative" data-password-toggle>
                        <input type="password" name="password" id="password"
                               autocomplete="current-password" required
                               data-password-input
                               @class([
                                   'block w-full rounded-lg border p-2.5 pr-11 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400',
                                   'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500' => $errors->has('password'),
                                   'border-gray-300 bg-gray-50 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600' => ! $errors->has('password'),
                               ])>
                        <button type="button" data-password-toggle-button
                                aria-label="{{ __('Show password') }}" aria-pressed="false" aria-controls="password"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg data-password-icon-show class="h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg data-password-icon-hide class="hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                                <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                                <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
                                <path d="m2 2 20 20"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="remember" class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white">
                        <input id="remember" name="remember" type="checkbox" value="1"
                               aria-describedby="remember-help"
                               class="h-4 w-4 rounded border-gray-300 bg-gray-50 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                               {{ old('remember') ? 'checked' : '' }}>
                        {{ __('Remember me') }}
                    </label>
                    <p id="remember-help" class="mt-1 pl-6 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Keeps you signed in after you close this browser. Signing out always ends it, so avoid this on shared devices.') }}
                    </p>
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                    {{ __('Sign in') }}
                </button>
            </form>
        </div>
    </div>
@endsection