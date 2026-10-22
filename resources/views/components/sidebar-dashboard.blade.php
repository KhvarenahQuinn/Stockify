{{-- Responsive shell for the sidebar. Hidden below `lg`, pinned open from `lg` up. --}}
<aside id="sidebar" class="fixed top-0 left-0 z-20 hidden h-full w-64 flex-shrink-0 flex-col pt-16 font-normal lg:flex" aria-label="Sidebar">
    <div class="relative flex min-h-0 flex-1 flex-col border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="flex-1 overflow-y-auto px-3 py-5">
            <ul class="space-y-1">
                {{ $slot }}
            </ul>
        </div>

        @isset($footer)
            {{ $footer }}
        @endisset
    </div>
</aside>

<div id="sidebarBackdrop" class="fixed inset-0 z-10 hidden bg-gray-900/50 lg:hidden dark:bg-gray-900/90" aria-hidden="true"></div>
