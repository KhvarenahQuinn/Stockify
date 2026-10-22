@php
    /**
     * Role-aware navigation mirroring the `role:` guards declared in routes/web.php.
     *
     * Icons are keyed once so a role menu only declares the links it adds, and
     * Route::has() hides a link whose route has not landed yet instead of blowing
     * up with a UrlGenerationException.
     */
    $icons = [
        'home' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
        'box' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
        'folder' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
        'tag' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.569 3z M6 6h.008v.008H6V6z',
        'truck' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.5 1.5 0 01-1.41-1.028L1.5 12.75m15 6a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m0 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        'users' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'settings' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124l.214-.128zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'swap' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 12m0 0l-4.5 4.5M21 12H7.5',
        'clipboard-check' => 'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z',
        'chart' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'document' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'inbox' => 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3',
        'check-badge' => 'M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z',
    ];

    $masterData = [
        ['route' => 'products.index', 'title' => 'Products', 'icon' => 'box'],
        ['route' => 'categories.index', 'title' => 'Categories', 'icon' => 'folder'],
        ['route' => 'attributes.index', 'title' => 'Attributes', 'icon' => 'tag'],
        ['route' => 'suppliers.index', 'title' => 'Suppliers', 'icon' => 'truck'],
    ];

    // The two queues are split by role rather than hidden: staff work the
    // inspection queue and submit, managers approve. Declaring both per role
    // keeps the sidebar an honest map of what that person can actually do.
    $warehouseQueues = [
        ['route' => 'stock-transactions.queue', 'title' => 'Inspection Queue', 'icon' => 'clipboard-check'],
        ['route' => 'stock-transactions.approvals', 'title' => 'Approvals', 'icon' => 'check-badge'],
    ];

    $warehouse = [
        ['route' => 'stock-transactions.index', 'title' => 'Stock Transactions', 'icon' => 'swap'],
        ['route' => 'stock-transactions.create', 'title' => 'New Movement', 'icon' => 'inbox'],
        ['route' => 'stock-opnames.index', 'title' => 'Stock Opname', 'icon' => 'clipboard-check'],
    ];

    $reporting = [
        ['route' => 'reports.stock', 'title' => 'Stock Report', 'icon' => 'chart'],
        ['route' => 'reports.transactions', 'title' => 'Transaction Report', 'icon' => 'document'],
        ['route' => 'reports.activity', 'title' => 'Activity Log', 'icon' => 'clock'],
    ];

    $navigation = [
        // Admin: everything, including the pages gated behind `role:admin`.
        'admin' => [
            'Administration' => [
                ['route' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'admin.users.index', 'title' => 'User Management', 'icon' => 'users'],
                ['route' => 'settings.index', 'title' => 'Settings', 'icon' => 'settings'],
            ],
            'Master Data' => $masterData,
            'Warehouse' => array_merge($warehouseQueues, $warehouse),
            'Reports' => $reporting,
        ],
        // Manager: the catalogue plus stock and reporting, no user or settings admin.
        'warehouse_manager' => [
            'Dashboard' => [
                ['route' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'home'],
            ],
            'Master Data' => $masterData,
            'Warehouse' => array_merge($warehouseQueues, $warehouse),
            'Reports' => $reporting,
        ],
        // Staff: the queues they operate on, nothing else.
        'warehouse_staff' => [
            'Task Queue' => [
                ['route' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'stock-transactions.queue', 'title' => 'Inspection Queue', 'icon' => 'clipboard-check'],
                ['route' => 'stock-transactions.index', 'title' => 'Stock Transactions', 'icon' => 'swap'],
                ['route' => 'stock-transactions.create', 'title' => 'New Movement', 'icon' => 'inbox'],
                ['route' => 'stock-opnames.index', 'title' => 'Input Stock Opname', 'icon' => 'clipboard-check'],
                ['route' => 'products.index', 'title' => 'Products', 'icon' => 'box'],
            ],
        ],
    ];

    // Drop links whose route is not registered yet, and any group left empty.
    $groups = collect($navigation[auth()->user()?->role?->name] ?? [])
        ->map(fn ($items) => array_values(array_filter($items, fn ($item) => Route::has($item['route']))))
        ->reject(fn ($items) => $items === [])
        ->all();
@endphp

{{-- The navbar is a fixed 64px (top-0) bar that spans the full width, so the
     drawer starts at top-16 and is shortened by the same amount. At top-0 the
     first section heading rendered underneath the navbar. --}}
<aside id="drawer-navigation" tabindex="-1" aria-label="{{ __('Sidebar') }}"
       class="fixed left-0 top-16 z-40 h-[calc(100vh-4rem)] w-64 -translate-x-full overflow-x-hidden overflow-y-auto border-r border-gray-200 bg-white transition-[width,transform] duration-300 ease-in-out lg:translate-x-0 dark:border-gray-700 dark:bg-gray-800">
    <div class="flex min-h-full flex-col px-3 py-5">
        <div class="flex-1">
            @foreach ($groups as $label => $items)
                <h2 class="sidebar-heading whitespace-nowrap px-2 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    {{ __($label) }}
                </h2>

                <ul class="mb-4 space-y-1">
                    @foreach ($items as $item)
                        @php($active = request()->routeIs($item['route']))
                        <li>
                            <a href="{{ route($item['route']) }}"
                               data-sidebar-tooltip="{{ __($item['title']) }}"
                               aria-label="{{ __($item['title']) }}"
                               @if ($active) aria-current="page" @endif
                               @class([
                                   'sidebar-link flex items-center gap-3 rounded-lg p-2 text-sm font-medium transition-colors duration-200',
                                   'bg-primary-100 text-gray-900 font-semibold dark:bg-primary-900/40 dark:text-white' => $active,
                                   'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' => ! $active,
                               ])>
                                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}"
                                     fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}"/>
                                </svg>
                                <span class="sidebar-label whitespace-nowrap">{{ __($item['title']) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>

        @if (filled($groups))
            <div class="sidebar-user border-t border-gray-200 pt-4 dark:border-gray-700">
                <p class="px-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Signed in as') }} <span class="font-medium text-gray-900 dark:text-white">{{ auth()->user()->role->display_name }}</span>
                </p>
            </div>
        @endif
    </div>
</aside>
