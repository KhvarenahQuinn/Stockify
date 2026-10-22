@extends('layouts.app')

@section('content')
    @php
        /**
         * The backend issue for master data may land in either order, so every
         * route reference here is probed first. A missing endpoint hides the
         * control that needed it rather than throwing a UrlGenerationException.
         */
        $categories = $categories ?? collect();
        $canManage = auth()->user()?->role?->name === 'admin';

        // The controller hands over a paginator, but a half-wired endpoint may
        // pass a plain collection, so totals are read through whichever it is.
        $paginator = $categories instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $categories : null;
        $total = $paginator?->total() ?? $categories->count();

        $canCreate = $canManage && Route::has('categories.store');
        $canUpdate = $canManage && Route::has('categories.update');
        $canDelete = $canManage && Route::has('categories.destroy');
        $showsActions = $canUpdate || $canDelete;
    @endphp

    <div class="p-4">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Categories') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __($total === 1 ? ':count category' : ':count categories', ['count' => format_number($total)]) }}
                    <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                    {{ __('slugs drive the inventory API routes') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @unless ($canManage)
                    <x-master.read-only-note feature="categories" />
                @endunless

                @if ($canCreate)
                    <button type="button" data-modal-target="create-category-modal" data-modal-toggle="create-category-modal"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        {{ __('Add category') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" action="{{ Route::has('categories.index') ? route('categories.index') : url()->current() }}"
                  class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center dark:border-gray-700">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <label for="q" class="sr-only">{{ __('Search categories') }}</label>
                    <input type="search" name="q" id="q" value="{{ request('q') }}" placeholder="{{ __('Search name or slug') }}"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 pl-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        {{ __('Search') }}
                    </button>
                    @if (request()->has('q'))
                        <a href="{{ Route::has('categories.index') ? route('categories.index') : url()->current() }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <caption class="sr-only">{{ __('Product categories') }}</caption>
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-3 py-3 lg:px-6">{{ __('Category') }}</th>
                            <th scope="col" class="hidden px-3 py-3 md:table-cell lg:px-6">{{ __('Slug') }}</th>
                            <th scope="col" class="hidden px-3 py-3 md:table-cell lg:px-6">{{ __('Description') }}</th>
                            <th scope="col" class="px-3 py-3 text-right lg:px-6">{{ __('Products') }}</th>
                            <th scope="col" class="hidden px-3 py-3 md:table-cell lg:px-6">{{ __('Created') }}</th>
                            @if ($showsActions)
                                <th scope="col" class="px-3 py-3 text-right lg:px-6"><span class="sr-only">{{ __('Actions') }}</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr class="border-b border-gray-200 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40">
                                <th scope="row" class="break-words px-3 py-3 font-medium text-gray-900 md:py-4 lg:px-6 dark:text-white">
                                    {{ $category->name }}
                                    <code class="mt-1 block break-all font-mono text-[11px] font-normal text-gray-500 md:hidden dark:text-gray-400">{{ $category->slug }}</code>
                                </th>
                                <td class="hidden px-3 py-3 md:table-cell md:py-4 lg:px-6">
                                    <code class="break-all rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $category->slug }}</code>
                                </td>
                                <td class="hidden max-w-xs px-3 py-3 md:table-cell md:py-4 lg:px-6">
                                    @if ($category->description)
                                        <span class="line-clamp-2" title="{{ $category->description }}">{{ $category->description }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">&mdash;</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums md:py-4 lg:px-6">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-200' => $category->products_count > 0,
                                        'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $category->products_count === 0,
                                    ])>
                                        {{ format_number($category->products_count) }}
                                    </span>
                                </td>
                                <td class="hidden whitespace-nowrap px-3 py-3 text-gray-500 md:table-cell md:py-4 lg:px-6 dark:text-gray-400">
                                    {{ $category->created_at?->translatedFormat('d M Y') }}
                                </td>
                                @if ($showsActions)
                                    <td class="whitespace-nowrap px-3 py-3 text-right md:py-4 lg:px-6">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            @if ($canUpdate)
                                                <button type="button"
                                                        data-modal-target="edit-category-{{ $category->id }}"
                                                        data-modal-toggle="edit-category-{{ $category->id }}"
                                                        aria-label="{{ __('Edit') }} {{ $category->name }}"
                                                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 md:h-auto md:w-auto md:px-2.5 md:py-1.5 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                                    </svg>
                                                    <span class="hidden md:inline">{{ __('Edit') }}</span>
                                                </button>
                                            @endif

                                            @if ($canDelete)
                                                <button type="button"
                                                        data-modal-target="delete-category-{{ $category->id }}"
                                                        data-modal-toggle="delete-category-{{ $category->id }}"
                                                        aria-label="{{ __('Delete') }} {{ $category->name }}"
                                                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-200 md:h-auto md:w-auto md:px-2.5 md:py-1.5 dark:text-red-400 dark:hover:bg-gray-700 dark:focus:ring-red-800/40">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                    </svg>
                                                    <span class="hidden md:inline">{{ __('Delete') }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showsActions ? 6 : 5 }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    @if (request('q'))
                                        {{ __('No categories match “:query”.', ['query' => request('q')]) }}
                                    @else
                                        {{ __('No categories yet.') }}
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($paginator?->hasPages())
                <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>
    </div>

    @if ($canCreate)
        @include('categories.partials.create-modal')
    @endif

    @foreach ($categories as $category)
        @if ($canUpdate)
            @include('categories.partials.edit-modal', ['category' => $category])
        @endif
        @if ($canDelete)
            @include('categories.partials.delete-modal', ['category' => $category])
        @endif
    @endforeach

    <script>
        // Flowbite only wires its data attributes on load, so a validation
        // redirect has to re-init before we reopen the modal that owns the
        // errors. Each form carries a hidden `_form` key, so there is no
        // guessing which modal to reopen.
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof window.initFlowbite === 'function') {
                window.initFlowbite();
            }

            const form = @json(old('_form'));

            if (form) {
                document.querySelector(`[data-modal-toggle="${form}"]`)?.click();
            }
        });

        // Slug mirror. The name field drives the slug until the user edits the
        // slug by hand, after which it is left alone. The steps below track
        // Illuminate\Support\Str::slug in the same order — underscores become
        // separators first, then anything that is not a letter, digit, space or
        // separator is dropped — so the preview matches what the server stores.
        document.addEventListener('DOMContentLoaded', () => {
            const toSlug = (value) => value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/_/g, '-')
                .toLowerCase()
                .replace(/[^\p{L}\p{N}\s-]/gu, '')
                .replace(/[\s-]+/g, '-')
                .replace(/^-|-$/g, '');

            document.querySelectorAll('[data-slug-source]').forEach((source) => {
                const target = document.getElementById(source.dataset.slugSource);

                if (!target) {
                    return;
                }

                let touched = false;

                source.addEventListener('input', () => {
                    if (!touched) {
                        target.value = toSlug(source.value);
                    }
                });

                target.addEventListener('input', () => {
                    touched = true;
                });
            });
        });
    </script>
@endsection