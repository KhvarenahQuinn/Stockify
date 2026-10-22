/**
 * Application settings page.
 *
 * The critical-stock toggle is a checkbox under a styled switch, so the
 * fields below it are dimmed rather than disabled. Disabling them would stop
 * the address being submitted, and the page promises that switching the
 * alerts off does not lose the settings underneath.
 */
function initSettings() {
    const toggle = document.getElementById('email_alerts_enabled');
    const fields = document.querySelector('[data-alert-fields]');

    if (!toggle || !fields) {
        return;
    }

    const label = toggle.closest('.relative')?.querySelector('label[role="switch"]');

    const sync = () => {
        const on = toggle.checked;

        // Dimmed, not disabled: the values stay in the form and keep being
        // submitted, so turning the alerts off and on again does not lose the
        // address or the threshold.
        fields.classList.toggle('opacity-50', !on);

        label?.setAttribute('aria-checked', on ? 'true' : 'false');
    };

    toggle.addEventListener('change', sync);
    sync();
}

document.addEventListener('DOMContentLoaded', initSettings);