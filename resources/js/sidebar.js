const STORAGE_KEY = 'sidebar-collapsed';
const root = document.documentElement;
const sidebar = document.getElementById('drawer-navigation');
const toggle = document.getElementById('sidebar-toggle');

// The auth layout and any page without the app shell simply skip this module.
if (sidebar && toggle) {
    const desktop = window.matchMedia('(min-width: 1024px)');
    const collapseLabel = toggle.dataset.collapseLabel || 'Collapse sidebar';
    const expandLabel = toggle.dataset.expandLabel || 'Expand sidebar';
    let tooltip = null;

    const isCollapsed = () => root.classList.contains('sidebar-collapsed');

    const ensureTooltip = () => {
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'sidebar-tooltip';
            tooltip.setAttribute('aria-hidden', 'true');
            tooltip.className = 'pointer-events-none fixed z-[80] hidden -translate-y-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white shadow-lg dark:bg-gray-700';
            document.body.appendChild(tooltip);
        }

        return tooltip;
    };

    const hideTooltip = () => {
        if (tooltip) {
            tooltip.classList.add('hidden');
        }
    };

    const showTooltip = (link) => {
        if (!isCollapsed() || !desktop.matches) {
            return;
        }

        const label = link.dataset.sidebarTooltip;

        if (!label) {
            return;
        }

        const element = ensureTooltip();
        const rect = link.getBoundingClientRect();

        element.textContent = label;
        element.style.left = `${rect.right + 12}px`;
        element.style.top = `${rect.top + rect.height / 2}px`;
        element.classList.remove('hidden');
    };

    const syncToggle = () => {
        const collapsed = isCollapsed();

        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? expandLabel : collapseLabel);
    };

    const setCollapsed = (collapsed, persist) => {
        root.classList.toggle('sidebar-collapsed', collapsed);

        if (persist) {
            try {
                localStorage.setItem(STORAGE_KEY, collapsed ? 'true' : 'false');
            } catch (error) {
                // The state still works for this page when storage is blocked.
            }
        }

        syncToggle();
        hideTooltip();
    };

    toggle.addEventListener('click', () => setCollapsed(!isCollapsed(), true));

    sidebar.querySelectorAll('[data-sidebar-tooltip]').forEach((link) => {
        link.addEventListener('mouseenter', () => showTooltip(link));
        link.addEventListener('mouseleave', hideTooltip);
        link.addEventListener('focus', () => showTooltip(link));
        link.addEventListener('blur', hideTooltip);
        link.addEventListener('click', hideTooltip);
    });

    sidebar.addEventListener('scroll', hideTooltip, { passive: true });
    window.addEventListener('resize', hideTooltip);
    desktop.addEventListener('change', hideTooltip);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideTooltip();
        }
    });
    window.addEventListener('pageshow', syncToggle);

    syncToggle();
}
