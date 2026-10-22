import ApexCharts from 'apexcharts';

/**
 * Dashboard charts.
 *
 * ApexCharts is imported as a module, so it is never on `window`. The previous
 * widget checked for a global and Alpine's `x-init`, neither of which exists in
 * this build, so the guard silently swallowed the failure and the card rendered
 * as an empty box with a data table underneath it.
 *
 * Every chart here is driven by a JSON config on its own mount point, so the
 * server owns the data and the script owns only the presentation.
 */

/**
 * Colours for the active theme.
 *
 * Axis labels are small text, so they need 4.5:1 - and no single grey clears
 * that in both themes: gray-500 is 4.83:1 on white but 3.04:1 on gray-800,
 * while gray-400 is the reverse at 2.54:1 and 5.78:1. Swapping them is the only
 * way both themes pass, which is why the theme is read per render rather than
 * baked in once.
 */
const theme = () => {
    const dark = document.documentElement.classList.contains('dark');

    return dark
        ? {
              grid: '#374151',       // gray-700
              axis: '#9CA3AF',       // gray-400 - 5.78:1 on the gray-800 card
              tooltipBg: '#1F2937',  // gray-800
              tooltipFg: '#F3F4F6',  // gray-100
          }
        : {
              grid: '#E5E7EB',       // gray-200
              axis: '#6B7280',       // gray-500 - 4.83:1 on white
              tooltipBg: '#FFFFFF',
              tooltipFg: '#111827',  // gray-900
          };
};

const FONT = 'Inter, sans-serif';

function stockMovementOptions(config) {
    const t = theme();

    return {
        chart: {
            type: config.type || 'bar',
            height: config.height || 320,
            fontFamily: FONT,
            foreColor: t.axis,
            toolbar: { show: false },
            zoom: { enabled: false },
            // Keeps the card from reflowing as bars animate in.
            animations: { enabled: true, speed: 350 },
        },
        series: config.series,
        colors: config.colors,
        plotOptions: {
            bar: { borderRadius: 4, columnWidth: '45%' },
            area: { fillOpacity: 0.18 },
        },
        dataLabels: { enabled: false },
        stroke: { width: config.type === 'area' ? [2, 2] : [0, 0], curve: 'smooth' },
        fill: {
            type: config.type === 'area' ? 'gradient' : 'solid',
            gradient: { opacityFrom: 0.28, opacityTo: 0.02 },
        },
        grid: {
            show: true,
            borderColor: t.grid,
            strokeDashArray: 3,
            padding: { left: 8, right: 8, top: 0, bottom: 0 },
        },
        xaxis: {
            categories: config.categories,
            labels: {
                style: { colors: t.axis, fontSize: '12px', fontWeight: 500 },
            },
            axisBorder: { color: t.grid },
            axisTicks: { color: t.grid },
            crosshairs: {
                stroke: { color: t.grid, width: 1, dashArray: 4 },
            },
        },
        yaxis: {
            labels: {
                style: { colors: t.axis, fontSize: '12px', fontWeight: 500 },
                formatter: (value) => Number(value).toLocaleString(document.documentElement.lang || 'en-US'),
            },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            fontFamily: FONT,
            fontSize: '13px',
            fontWeight: 500,
            markers: { width: 8, height: 8, radius: 2 },
            labels: { colors: t.axis },
            itemMargin: { horizontal: 12, vertical: 4 },
        },
        tooltip: {
            shared: true,
            intersect: false,
            // ApexCharts' own dark/light tooltip themes, rather than hand-rolled
            // colours: they are the values the library actually applies, and an
            // unknown option key is silently dropped.
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
            style: { fontSize: '13px', fontFamily: FONT },
            x: { show: true },
        },
        responsive: [
            {
                breakpoint: 640,
                options: {
                    legend: { position: 'bottom', horizontalAlign: 'left' },
                    xaxis: { labels: { rotate: -45, hideOverlappingLabels: true } },
                    chart: { height: 280 },
                },
            },
        ],
    };
}

/**
 * Mount every chart on the page and keep it in step with the theme toggle.
 *
 * One shared listener re-themes all charts rather than each mount adding its
 * own, because the toggle event fires once per click and N listeners would
 * each rebuild the same options N times over.
 */
function initDashboardCharts() {
    const mounts = Array.from(document.querySelectorAll('[data-dashboard-chart]'));

    if (mounts.length === 0) {
        return;
    }

    const instances = [];

    mounts.forEach((mount) => {
        let config;

        try {
            config = JSON.parse(mount.dataset.dashboardChart || '{}');
        } catch (e) {
            config = {};
        }

        // An empty series would render an axis with no data and a legend with
        // nothing in it, which reads as a broken card rather than a quiet month.
        const hasData = (config.series || []).some((s) => (s.data || []).some((v) => Number(v) !== 0));

        mount.querySelector('[data-chart-empty]')?.classList.toggle('hidden', hasData);
        mount.querySelector('[data-chart-canvas]')?.classList.toggle('hidden', !hasData);

        if (!hasData) {
            return;
        }

        const chart = new ApexCharts(mount.querySelector('[data-chart-canvas]'), stockMovementOptions(config));
        chart.render();
        instances.push({ chart, config });
    });

    if (instances.length === 0) {
        return;
    }

    document.addEventListener('dark-mode', () => {
        instances.forEach(({ chart, config }) => {
            chart.updateOptions(stockMovementOptions(config), false, false);
        });
    });
}

document.addEventListener('DOMContentLoaded', initDashboardCharts);