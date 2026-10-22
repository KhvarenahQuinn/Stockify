@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Application Settings') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('These settings apply to every user of Stockify.') }}
        </p>
    </div>

    {{-- A non-admin reaching this page is a routing mistake, not a
         permissions question, so the page says so plainly rather than
         rendering a form they cannot use. --}}
    @unless (auth()->user()?->role?->name === 'admin')
        <x-master.read-only-note :feature="__('application settings')" />
    @else
        <form method="POST" action="{{ Route::has('settings.update') ? route('settings.update') : '#' }}"
              enctype="multipart/form-data"
              data-settings-form
              class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            {{-- Brand ------------------------------------------------------------ --}}
            <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                     aria-labelledby="brand-heading">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h2 id="brand-heading" class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('Branding') }}
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('The name and logo shown in the sidebar and on printed reports.') }}
                    </p>
                </div>

                <div class="space-y-5 p-4">
                    <x-form.input name="app_name"
                                  :label="__('Application name')"
                                  :value="old('app_name', $values['app_name'])"
                                  :required="true"
                                  :hint="__('Shown in the sidebar, the browser tab and exported report headers.')" />

                    <x-form.file-upload name="app_logo"
                                        :label="__('Application logo')"
                                        :current-url="$logoUrl"
                                        remove-name="remove_logo"
                                        accept="image/jpeg,image/png,image/webp"
                                        :max-kilobytes="1024"
                                        :hint="__('JPEG, PNG or WebP. Maximum 1 MB. Uploading replaces the current logo.')" />
                </div>
            </section>

            {{-- Stock ------------------------------------------------------------- --}}
            <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                     aria-labelledby="stock-heading">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h2 id="stock-heading" class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('Stock') }}
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('The default reorder point for newly created products.') }}
                    </p>
                </div>

                <div class="p-4">
                    <x-form.input name="default_min_stock"
                                  :label="__('Default low-stock threshold')"
                                  type="number"
                                  :value="old('default_min_stock', $values['default_min_stock'])"
                                  :required="true"
                                  :hint="__('Pre-fills the reorder point when creating a new product. Existing products keep their own.')" />
                </div>
            </section>

            {{-- Notifications ----------------------------------------------------- --}}
            <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                     aria-labelledby="alerts-heading">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h2 id="alerts-heading" class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('Critical stock alerts') }}
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Email the warehouse when stock falls to a level worth acting on.') }}
                    </p>
                </div>

                <div class="space-y-5 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <label for="email_alerts_enabled"
                                   class="block text-sm font-medium text-gray-900 dark:text-white">
                                {{ __('Send email alerts') }}
                            </label>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Turn this off to stop low-stock emails without losing the settings below.') }}
                            </p>
                        </div>

                        {{-- A real checkbox: the switch is a styled label around
                             it, so the input keeps its own state, its own
                             name and the keyboard behaviour of a checkbox. --}}
                        <div class="relative shrink-0">
                            <input type="checkbox" name="email_alerts_enabled" id="email_alerts_enabled" value="1"
                                   @checked(old('email_alerts_enabled', $values['email_alerts_enabled']) === '1')
                                   class="peer sr-only">
                            <label for="email_alerts_enabled"
                                   class="block h-6 w-11 cursor-pointer rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary-500 dark:bg-gray-700 dark:after:border-gray-600 dark:peer-checked:bg-primary-600"
                                   role="switch"
                                   aria-checked="{{ old('email_alerts_enabled', $values['email_alerts_enabled']) === '1' ? 'true' : 'false' }}">
                                <span class="sr-only">{{ __('Send email alerts for critical stock levels') }}</span>
                            </label>
                        </div>
                    </div>

                    <div data-alert-fields
                         class="space-y-5 border-t border-gray-200 pt-5 dark:border-gray-700 {{ old('email_alerts_enabled', $values['email_alerts_enabled']) === '1' ? '' : 'opacity-50' }}">
                        <x-form.input name="email_alerts_recipient"
                                      :label="__('Send alerts to')"
                                      type="email"
                                      :value="old('email_alerts_recipient', $values['email_alerts_recipient'])"
                                      placeholder="warehouse@example.com"
                                      :hint="__('Where the low-stock notifications are sent.')" />

                        <x-form.input name="email_alerts_min_stock"
                                      :label="__('Alert at or below this stock level')"
                                      type="number"
                                      :value="old('email_alerts_min_stock', $values['email_alerts_min_stock'])"
                                      :hint="__('A product at or under this level raises an alert. Zero alerts on everything that is out of stock.')" />
                    </div>
                </div>
            </section>

            {{-- Language --------------------------------------------------------- --}}
            <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                     aria-labelledby="language-heading">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h2 id="language-heading" class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('Language') }}
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Choose the language used in your account. This preference is saved to your profile.') }}
                    </p>
                </div>

                <div class="p-4">
                    <x-form.select name="locale"
                                   :label="__('Language / Bahasa')"
                                   :options="[
                                       ['value' => 'en', 'label' => 'English (EN)'],
                                       ['value' => 'id', 'label' => 'Bahasa Indonesia (ID)'],
                                   ]"
                                   :value="$locale"
                                   :blank="false"
                                   :required="true"
                                   :hint="__('The interface updates as soon as you save.')" />
                </div>
            </section>

            <div class="flex items-center gap-3">
                <button type="submit" data-settings-submit
                        class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400">
                    {{ __('Save settings') }}
                </button>

                @if (Route::has('dashboard'))
                    <a href="{{ route('dashboard') }}"
                       class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                        {{ __('Cancel') }}
                    </a>
                @endif
            </div>
        </form>
    @endunless
</div>
@endsection