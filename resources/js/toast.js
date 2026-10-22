/**
 * Toast notifications.
 *
 * A toast region is rendered server-side with one template per tone, so the
 * markup and the colour classes exist in exactly one place (Blade). This file
 * only clones a template, writes the message, and manages the dismissal.
 *
 * The global API is window.toast:
 *
 *   toast.success('Settings updated.');
 *   toast.error('That file is not an image.');
 *
 * Flash messages are rendered server-side into the same region, so a form
 * that flashes 'success' produces a toast without the view knowing this file
 * exists.
 */

const DEFAULT_DURATION = 4000;

const TONES = ['success', 'error', 'warning', 'info'];

const prefersReducedMotion = () =>
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Remove a toast.
 *
 * The element is hidden first and only then removed, so the exit transition
 * has something to animate. Without a reduced-motion preference the element
 * lingers for the length of the transition; with one it goes immediately.
 */
function dismiss(toast) {
    if (!toast || toast.dataset.toastClosing === '1') {
        return;
    }

    toast.dataset.toastClosing = '1';

    const remove = () => toast.remove();

    if (prefersReducedMotion()) {
        remove();
        return;
    }

    toast.classList.add('transition', 'duration-200', 'ease-out', 'opacity-0', 'translate-x-2');
    window.setTimeout(remove, 200);
}

/**
 * Start the auto-dismiss timer.
 *
 * The timer pauses while the pointer is over the toast: a message that
 * disappears while somebody is reading it is worse than no message at all.
 */
function startTimer(toast) {
    const duration = Number(toast.dataset.toastDuration || DEFAULT_DURATION);

    if (!Number.isFinite(duration) || duration <= 0) {
        return;
    }

    let remaining = duration;
    let startedAt = Date.now();
    let timer = null;

    const begin = () => {
        if (timer !== null) {
            return;
        }
        startedAt = Date.now();
        timer = window.setTimeout(() => dismiss(toast), remaining);
    };

    const pause = () => {
        if (timer === null) {
            return;
        }
        window.clearTimeout(timer);
        timer = null;
        remaining = Math.max(0, remaining - (Date.now() - startedAt));
    };

    begin();

    toast.addEventListener('mouseenter', pause);
    toast.addEventListener('mouseleave', begin);
    toast.addEventListener('focusin', pause);
    toast.addEventListener('focusout', begin);
}

/**
 * Wire a toast that is already on the page.
 */
function attach(toast) {
    if (!toast || toast.dataset.toastWired === '1') {
        return;
    }

    toast.dataset.toastWired = '1';

    toast.querySelector('[data-toast-close]')?.addEventListener('click', () => dismiss(toast));
    startTimer(toast);
}

/**
 * Raise a toast the script created.
 *
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number} duration
 */
function show(message, type = 'info', duration = DEFAULT_DURATION) {
    const region = document.querySelector('[data-toast-region]');

    if (!region) {
        return null;
    }

    const tone = TONES.includes(type) ? type : 'info';
    const template = document.querySelector(`[data-toast-template="${tone}"]`);

    if (!template) {
        return null;
    }

    const toast = template.content.firstElementChild.cloneNode(true);

    toast.dataset.toastDuration = String(duration);

    const text = toast.querySelector('[data-toast-message]');
    if (text) {
        text.textContent = message;
    }

    region.append(toast);
    attach(toast);

    return toast;
}

const toast = {
    show,
    success: (message, duration) => show(message, 'success', duration),
    error: (message, duration) => show(message, 'error', duration),
    warning: (message, duration) => show(message, 'warning', duration),
    info: (message, duration) => show(message, 'info', duration),
};

window.toast = toast;

function initToasts() {
    document.querySelectorAll('[data-toast]').forEach(attach);
}

document.addEventListener('DOMContentLoaded', initToasts);