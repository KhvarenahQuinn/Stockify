@props([
    'chart',
    'title' => __('Stock movement'),
    'subtitle' => __('Units received against units issued, by month.'),
])

@php
    // $chart is null on roles whose dashboard skips the movement query. The
    // component still renders its heading and empty state rather than throwing.
    $apex = ($chart['apexcharts'] ?? null) ?: [];
    $series = $apex['series'] ?? [];
    $categories = $apex['categories'] ?? [];
    $inData = $series[0]['data'] ?? [];
    $outData = $series[1]['data'] ?? [];

    // Built here rather than inline so Blade never parses a nested literal and
    // the JSON is escaped exactly once. The script reads this and nothing else.
    $configJson = json_encode([
        'type' => $apex['type'] ?? 'bar',
        'height' => $apex['height'] ?? 320,
        'series' => $series,
        'categories' => $categories,
        'colors' => $apex['colors'] ?? [],
    ]);

    $hasData = collect($series)->contains(fn ($s) => collect($s['data'] ?? [])->contains(fn ($v) => (float) $v !== 0.0));
@endphp

<section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
         aria-labelledby="stock-movement-heading">
    <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
        <div>
            <h2 id="stock-movement-heading" class="text-base font-semibold text-gray-900 dark:text-white">
                {{ $title }}
            </h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
        </div>

        <div class="flex items-center gap-3 text-xs font-medium text-gray-600 dark:text-gray-300">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-green-500" aria-hidden="true"></span>
                {{ __('Goods in') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-orange-500" aria-hidden="true"></span>
                {{ __('Goods out') }}
            </span>
        </div>
    </div>

    {{-- The mount point. dashboard-charts.js owns everything below this line. --}}
    <div data-dashboard-chart data-chart-config="{{ $configJson }}">
        <div data-chart-canvas class="min-h-[280px]"></div>
    </div>

    {{-- Replaced by the script when there is data to draw. --}}
    <p data-chart-empty @if ($hasData) hidden @endif
       class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('No stock movement recorded in this period yet.') }}
    </p>

    {{--
        The text alternative. A chart is not readable with a screen reader, so
        the same figures ship as a table; it is also what carries the data when
        the script is blocked or the chart is still animating in.
    --}}
    <details class="mt-4 border-t border-gray-200 pt-3 dark:border-gray-700">
        <summary class="cursor-pointer text-xs font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
            {{ __('View the figures as a table') }}
        </summary>

        <div class="mt-2 max-h-64 overflow-y-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">{{ __('Units of stock received and issued in each month') }}</caption>
                <thead class="sticky top-0 bg-gray-50 text-xs uppercase tracking-wide text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-3 py-2">{{ __('Month') }}</th>
                        <th scope="col" class="px-3 py-2 text-right">{{ __('Goods in') }}</th>
                        <th scope="col" class="px-3 py-2 text-right">{{ __('Goods out') }}</th>
                        <th scope="col" class="px-3 py-2 text-right">{{ __('Net') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($categories as $i => $label)
                        @php
                            $net = ($inData[$i] ?? 0) - ($outData[$i] ?? 0);
                        @endphp
                        <tr>
                            <th scope="row" class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $label }}</th>
                            <td class="px-3 py-2 text-right tabular-nums">{{ format_number($inData[$i] ?? 0) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ format_number($outData[$i] ?? 0) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums {{ $net > 0 ? 'text-green-700 dark:text-green-400' : ($net < 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-600 dark:text-gray-300') }}">
                                {{ $net > 0 ? '+' : '' }}{{ format_number($net) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No months in range.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-gray-200 font-semibold dark:border-gray-700">
                    <tr>
                        <th scope="row" class="px-3 py-2 text-left text-gray-900 dark:text-white">{{ __('Total') }}</th>
                        <td class="px-3 py-2 text-right tabular-nums">{{ format_number($chart['totals']['in'] ?? 0) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ format_number($chart['totals']['out'] ?? 0) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            @php($totalNet = ($chart['totals']['in'] ?? 0) - ($chart['totals']['out'] ?? 0))
                            {{ $totalNet > 0 ? '+' : '' }}{{ format_number($totalNet) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </details>
</section>