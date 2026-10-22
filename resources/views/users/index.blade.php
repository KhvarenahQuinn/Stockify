@extends('layouts.app')

@section('content')
    @php
        // The reset-password endpoint lands with the backend issue; the UI stays
        // out of the way until the route exists.
        $canResetPassword = Route::has('admin.users.reset-password');
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Users') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __($users->total() === 1 ? ':count account' : ':count accounts', ['count' => format_number($users->total())]) }}
                </p>
            </div>

            <button type="button" data-modal-target="create-user-modal" data-modal-toggle="create-user-modal"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                {{ __('Add user') }}
            </button>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ route('admin.users.index') }}"
                  class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center dark:border-gray-700">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <label for="q" class="sr-only">{{ __('Search users') }}</label>
                    <input type="search" name="q" id="q" value="{{ request('q') }}" placeholder="{{ __('Search name or email') }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 pl-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                </div>

                <div class="sm:w-56">
                    <label for="role" class="sr-only">{{ __('Filter by role') }}</label>
                    <select name="role" id="role"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('All roles') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(request('role') === $role->name)>
                                {{ $role->display_name ?? $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        {{ __('Filter') }}
                    </button>
                    @if (request()->hasAny(['q', 'role']))
                        <a href="{{ route('admin.users.index') }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ __('User') }}</th>
                            <th scope="col" class="hidden px-6 py-3 md:table-cell">{{ __('Email') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Role') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="hidden px-6 py-3 lg:table-cell">{{ __('Created') }}</th>
                            <th scope="col" class="sticky right-0 z-10 bg-gray-50 px-6 py-3 text-right dark:bg-gray-700">
                                <span class="sr-only">{{ __('Actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php($initials = mb_strtoupper(mb_substr($user->name, 0, 1)))
                            {{-- `group` so the sticky cell can follow the row hover; without it the cell
                                 keeps its own white background and reads as a hole in a
                                 highlighted row. --}}
                            <tr class="group border-b border-gray-200 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40">
                                <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                            {{ $initials }}
                                        </span>
                                        <span class="max-w-[12rem] truncate md:max-w-none">{{ $user->name }}</span>
                                    </div>
                                </th>
                                <td class="hidden px-6 py-4 md:table-cell">
                                    <span class="block max-w-[16rem] truncate">{{ $user->email }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    {{ $user->role?->display_name ?? __('Unassigned') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active'
                                        ? 'bg-green-700 text-white'
                                        : 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200' }}">
                                        {{ __($user->status === 'active' ? 'Active' : 'Inactive') }}
                                    </span>
                                </td>
                                <td class="hidden whitespace-nowrap px-6 py-4 lg:table-cell">
                                    {{ $user->created_at?->translatedFormat('d M Y') }}
                                </td>
                                {{-- Sticky actions cell. position:sticky always creates a stacking context,
                                     which traps the dropdown inside it, so the JS at the
                                     foot of this file raises this cell's z-index while its
                                     menu is open. --}}
                                <td class="sticky right-0 z-10 whitespace-nowrap border-l border-gray-200 bg-white px-6 py-4 text-right group-hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:group-hover:bg-gray-700/40">
                                    <button type="button" id="user-actions-{{ $user->id }}-button"
                                            data-dropdown-toggle="user-actions-{{ $user->id }}"
                                            data-dropdown-placement="bottom-end"
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                                        {{ __('Actions') }}
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 10 6" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m1 1 4 4 4-4"/>
                                        </svg>
                                    </button>

                                    <div id="user-actions-{{ $user->id }}"
                                         class="z-50 hidden w-48 divide-y divide-gray-100 rounded-lg bg-white text-sm shadow dark:divide-gray-600 dark:bg-gray-700">
                                        <ul class="py-1" aria-labelledby="user-actions-{{ $user->id }}-button">
                                            <li>
                                                <button type="button" data-modal-target="edit-user-{{ $user->id }}" data-modal-toggle="edit-user-{{ $user->id }}"
                                                        class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    {{ __('Edit') }}
                                                </button>
                                            </li>
                                            @if ($canResetPassword)
                                                <li>
                                                    <button type="button" data-modal-target="reset-password-{{ $user->id }}" data-modal-toggle="reset-password-{{ $user->id }}"
                                                            class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        {{ __('Reset password') }}
                                                    </button>
                                                </li>
                                            @endif
                                            <li>
                                                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                            class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        {{ __($user->status === 'active' ? 'Deactivate' : 'Activate') }}
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                      onsubmit="return confirm('{{ __('Delete :name? This cannot be undone.', ['name' => $user->name]) }}');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                            class="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-600">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ __('No users match this search.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('users.partials.create-user-modal')

    @foreach ($users as $user)
        @include('users.partials.edit-user-modal', ['user' => $user])
        @if ($canResetPassword)
            @include('users.partials.reset-password-modal', ['user' => $user])
        @endif
    @endforeach

    <script>
        // A failed validation returns as a fresh page load, so rebind the Flowbite
        // data attributes and reopen the modal that owns the errors. Each form
        // carries a hidden `_form` key, so there is no guessing which one to reopen.
        document.addEventListener('DOMContentLoaded', () => {
            window.initFlowbite();

            const form = @json(old('_form'));

            if (form) {
                document.querySelector(`[data-modal-toggle="${form}"]`)?.click();
            }

            // The Actions cell is sticky, and position:sticky creates a stacking
            // context — so the dropdown inside it is painted within that cell
            // only. Later rows' sticky cells would otherwise cover an open menu.
            // Flowbite dispatches no show/hide events here, so visibility is read
            // off the menu's own `hidden` class.
            document.querySelectorAll('[data-dropdown-toggle]').forEach((toggle) => {
                const menu = document.getElementById(toggle.dataset.dropdownToggle);
                const cell = toggle.closest('td');

                if (!menu || !cell) {
                    return;
                }

                const sync = () => {
                    cell.classList.toggle('z-30', !menu.classList.contains('hidden'));
                };

                new MutationObserver(sync).observe(menu, {
                    attributes: true,
                    attributeFilter: ['class'],
                });
            });
        });
    </script>
@endsection
