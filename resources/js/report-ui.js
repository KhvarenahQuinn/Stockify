/**
 * Report export buttons.
 *
 * An export is a download, not a navigation: the page stays where it is and
 * the browser saves a file. Without feedback the button looks broken, and a
 * second click starts a second download. So the button shows a spinner and
 * tells the user the download is being prepared.
 *
 * The button is restored when the window regains focus, which is the signal
 * that the browser has finished the download. A timer is kept as a failsafe
 * for a download that never arrives.
 */
import { t } from './i18n';

function initExportButtons() {
    const reduce = typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-export-trigger]').forEach((button) => {
        button.addEventListener('click', (event) => {
            // Let the browser follow the link: the download has to start.
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
                return;
            }

            const label = button.dataset.exportLabel || button.textContent.trim();
            const format = button.dataset.exportFormat || 'file';
            const original = button.innerHTML;

            button.setAttribute('aria-busy', 'true');
            button.dataset.exportLabel = label;
            button.innerHTML = `
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 ${reduce ? '' : 'animate-spin'}" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    ${t('Preparing...')}
                </span>`;

            if (window.toast) {
                window.toast.info(t('Preparing your :format download...', { format: format.toUpperCase() }));
            }

            const restore = () => {
                button.removeAttribute('aria-busy');
                button.innerHTML = original;
                window.removeEventListener('focus', restore);
            };

            // The download completes around the time the window regains focus.
            window.addEventListener('focus', restore, { once: true });
            window.setTimeout(restore, 8000);
        });
    });
}

document.addEventListener('DOMContentLoaded', initExportButtons);