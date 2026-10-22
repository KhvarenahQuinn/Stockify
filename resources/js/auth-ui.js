/**
 * Password visibility toggle for the sign-in form.
 *
 * Alpine is not a dependency of this project, so this is the same plain-DOM
 * approach used by the other UI modules. The button only changes the input's
 * type; the submitted value, validation and autofill behaviour stay untouched.
 */
import { t } from './i18n';

function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((root) => {
        const input = root.querySelector('[data-password-input]');
        const button = root.querySelector('[data-password-toggle-button]');
        const showIcon = root.querySelector('[data-password-icon-show]');
        const hideIcon = root.querySelector('[data-password-icon-hide]');

        if (!input || !button) {
            return;
        }

        button.addEventListener('click', () => {
            const reveal = input.type === 'password';

            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-label', t(reveal ? 'Hide password' : 'Show password'));
            button.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            showIcon?.classList.toggle('hidden', reveal);
            hideIcon?.classList.toggle('hidden', !reveal);
        });
    });
}

document.addEventListener('DOMContentLoaded', initPasswordToggles);
