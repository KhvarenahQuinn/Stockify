{{-- Shared field styling so every user form in the index page matches. --}}
@php
    $field = 'block w-full rounded-lg border bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
    $label = 'mb-1.5 block text-sm font-medium text-gray-900 dark:text-white';
    $invalid = fn (string $key) => $errors->has($key) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600';
@endphp

<div id="create-user-modal" tabindex="-1" aria-hidden="true"
     class="fixed inset-x-0 top-0 z-50 hidden h-full max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden p-4 md:inset-0">
    <div class="relative max-h-full w-full max-w-2xl">
        <div class="relative rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="flex items-center justify-between rounded-t border-b border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Add user') }}</h3>
                <button type="button" data-modal-hide="create-user-modal"
                        class="rounded-lg bg-transparent p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                    <span class="sr-only">{{ __('Close') }}</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="p-4">
                @csrf
                <input type="hidden" name="_form" value="create-user-modal">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="create-name" class="{{ $label }}">{{ __('Name') }}</label>
                        <input type="text" name="name" id="create-name" value="{{ old('name') }}" required
                               class="{{ $field }} {{ $invalid('name') }}">
                        @error('name')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="create-email" class="{{ $label }}">{{ __('Email') }}</label>
                        <input type="email" name="email" id="create-email" value="{{ old('email') }}" required
                               placeholder="name@company.com"
                               class="{{ $field }} {{ $invalid('email') }}">
                        @error('email')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="create-phone" class="{{ $label }}">{{ __('Phone') }} <span class="font-normal text-gray-500 dark:text-gray-400">{{ __('(optional)') }}</span></label>
                        <input type="text" name="phone" id="create-phone" value="{{ old('phone') }}"
                               class="{{ $field }} {{ $invalid('phone') }}">
                        @error('phone')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="create-role_id" class="{{ $label }}">{{ __('Role') }}</label>
                        <select name="role_id" id="create-role_id" required
                                class="{{ $field }} {{ $invalid('role_id') }}">
                            <option value="">{{ __('Select a role') }}</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>
                                    {{ $role->display_name ?? $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="create-status" class="{{ $label }}">{{ __('Status') }}</label>
                        <select name="status" id="create-status" class="{{ $field }} {{ $invalid('status') }}">
                            <option value="active" @selected(old('status', 'active') === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>{{ __('Inactive') }}</option>
                        </select>
                        @error('status')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>


                    <div>
                        <label for="create-password" class="{{ $label }}">{{ __('Password') }}</label>
                        <input type="password" name="password" id="create-password" required
                               class="{{ $field }} {{ $invalid('password') }}">
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Minimum 8 characters.') }}</p>
                        @error('password')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="create-password_confirmation" class="{{ $label }}">{{ __('Confirm password') }}</label>
                        <input type="password" name="password_confirmation" id="create-password_confirmation" required
                               class="{{ $field }} {{ $invalid('password_confirmation') }}">
                        @error('password_confirmation')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit"
                            class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                        {{ __('Create user') }}
                    </button>
                    <button type="button" data-modal-hide="create-user-modal"
                            class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
