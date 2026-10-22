@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Add User') }}</h1>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:underline">{{ __('Back to users') }}</a>
    </div>

    <div class="max-w-2xl p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                           class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white {{ $errors->has('name') ? 'border-red-500' : '' }}"
                           required autofocus>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('Email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white {{ $errors->has('email') ? 'border-red-500' : '' }}"
                           placeholder="name@company.com" required>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('Phone') }}</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                           class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white {{ $errors->has('phone') ? 'border-red-500' : '' }}">
                    @error('phone')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('Role') }}</label>
                    <select name="role_id" id="role_id"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white {{ $errors->has('role_id') ? 'border-red-500' : '' }}"
                            required>
                        <option value="">{{ __('Select a role') }}</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                {{ $role->display_name ?? $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('Status') }}</label>
                    <select name="status" id="status"
                            class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="active" @selected(old('status', 'active') === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                    @error('status')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('Password') }}</label>
                    <input type="password" name="password" id="password"
                           class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white {{ $errors->has('password') ? 'border-red-500' : '' }}"
                           required>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Minimum 8 characters.') }}</p>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('Confirm password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                           required>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-center text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                    {{ __('Create user') }}
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection