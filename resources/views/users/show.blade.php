@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('User Details') }}</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-blue-600 hover:underline">{{ __('Edit') }}</a>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:underline">{{ __('Back to users') }}</a>
        </div>
    </div>

    <div class="max-w-3xl p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center mb-6">
            <div class="flex items-center justify-center w-16 h-16 text-xl font-semibold text-white rounded-full bg-primary-700">
                {{ strtoupper(mb_substr((string) $user->name, 0, 1)) }}
            </div>
            <div class="ml-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            </div>
        </div>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Name') }}</dt>
                <dd class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
                <dd class="text-sm text-gray-900 dark:text-white">{{ $user->email }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt>
                <dd class="text-sm text-gray-900 dark:text-white">{{ $user->phone ?? '—' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Role') }}</dt>
                <dd class="text-sm text-gray-900 dark:text-white">
                    {{ $user->role?->display_name ?? $user->role?->name ?? '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                <dd class="text-sm">
                    <span class="px-2 py-1 text-xs font-medium rounded {{ $user->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-400' }}">
                        {{ __(ucfirst((string) $user->status)) }}
                    </span>
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Created') }}</dt>
                <dd class="text-sm text-gray-900 dark:text-white">
                    {{ $user->created_at?->translatedFormat('d M Y H:i') ?? '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Last updated') }}</dt>
                <dd class="text-sm text-gray-900 dark:text-white">
                    {{ $user->updated_at?->translatedFormat('d M Y H:i') ?? '—' }}
                </dd>
            </div>
        </dl>

        <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.users.edit', $user) }}"
               class="px-5 py-2.5 text-sm font-medium text-center text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ __('Edit user') }}
            </a>

            <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="inline"
                  id="toggle-user-{{ $user->id }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    {{ __($user->status === 'active' ? 'Deactivate' : 'Activate') }}
                </button>
            </form>

            <button type="button"
                    data-modal-target="delete-user-{{ $user->id }}"
                    data-modal-toggle="delete-user-{{ $user->id }}"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                {{ __('Delete') }}
            </button>
        </div>
    </div>

    @include('users.partials.delete-user-modal', [
        'user' => $user,
        'references' => $deletionBlockers,
        'toggleFormId' => 'toggle-user-'.$user->id,
    ])
</div>

<script>
    // Flowbite only binds data-modal attributes when asked, and this page has no
    // other script of its own.
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.initFlowbite === 'function') {
            window.initFlowbite();
        }
    });
</script>
@endsection