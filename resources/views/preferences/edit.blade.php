@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Language preferences') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Choose the language used in your account. This preference is saved to your profile.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('preferences.update') }}" class="max-w-xl space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                 aria-labelledby="language-heading">
            <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                <h2 id="language-heading" class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ __('Language / Bahasa') }}
                </h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('The interface updates as soon as you save.') }}
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
                               :required="true" />
            </div>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400">
                {{ __('Save language') }}
            </button>

            @if (Route::has('dashboard'))
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </a>
            @endif
        </div>
    </form>
</div>
@endsection
