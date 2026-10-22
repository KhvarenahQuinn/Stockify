{{--
    System Administrator.

    Governance view: how big the catalogue is, how much money is sitting on the
    shelves, who can actually sign in, and what everyone has been touching.
    Nothing here is a task queue - approving stock is the manager's job.
--}}

<section aria-labelledby="admin-kpis" class="mb-6">
    <h2 id="admin-kpis" class="sr-only">{{ __('Key figures') }}</h2>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-dashboard.kpi-card :label="__('Total products')" :value="$metrics['total_products']" tone="primary"
                              route="products.index" />

        <x-dashboard.kpi-card :label="__('Goods in this month')"
                              :value="$metrics['monthly_in_total']" tone="matched"
                              :hint="format_number($metrics['transaction_in_count']).' '.__('movements all time')" />

        <x-dashboard.kpi-card :label="__('Goods out this month')"
                              :value="$metrics['monthly_out_total']" tone="warning"
                              :hint="format_number($metrics['transaction_out_count']).' '.__('movements all time')" />

        {{-- Active rather than total: a deprovisioned account is not access. --}}
        <x-dashboard.kpi-card :label="__('Active users')" :value="$metrics['active_user_count']" tone="primary"
                              :hint="$metrics['user_count'].' '.__('accounts provisioned')"
                              route="admin.users.index" />

        <x-dashboard.kpi-card :label="__('Stock value')"
                              :value="$metrics['total_stock_value']"
                              :tone="'neutral'" class="col-span-2 lg:col-span-1"
                              :hint="__('Stock on hand at purchase price')" />
    </div>
</section>

<div class="grid gap-6 lg:grid-cols-5">
    <div class="lg:col-span-3">
        <x-dashboard.stock-movement-chart :chart="$chart" />
    </div>

    <section class="lg:col-span-2"
             aria-labelledby="activity-heading">
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-700">
                <h2 id="activity-heading" class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ __('Recent system activity') }}
                </h2>

                @if (Route::has('reports.activity'))
                    <a href="{{ route('reports.activity') }}"
                       class="shrink-0 text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                        {{ __('Full audit log') }}
                    </a>
                @endif
            </div>

            <div class="max-h-[26rem] overflow-y-auto">
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($metrics['activity'] as $log)
                        <li class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                {{-- truncate + title: the feed is a fixed-height
                                     list, and a three-line description would
                                     push everything below it down. The full
                                     text stays reachable on hover and to a
                                     screen reader, which does not see title. --}}
                                <p class="min-w-0 flex-1 truncate text-sm text-gray-900 dark:text-white"
                                   title="{{ $log->description }}">
                                    {{ $log->description }}
                                </p>

                                <x-dashboard.action-badge :action="$log" size="sm" class="shrink-0" />
                            </div>

                            <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $log->user?->name ?? __('System') }}</span>
                                {{-- title carries the exact time; the visible
                                     text is the relative one a human reads. --}}
                                <time datetime="{{ $log->created_at?->toIso8601String() }}"
                                      title="{{ $log->created_at?->translatedFormat('d M Y, H:i') }}">
                                    {{ $log->created_at?->diffForHumans() }}
                                </time>
                                @if ($log->ip_address)
                                    <span class="font-mono">{{ $log->ip_address }}</span>
                                @endif
                            </p>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No activity recorded yet.') }}
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>
</div>