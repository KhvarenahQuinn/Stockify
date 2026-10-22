/**
 * Tiny translation helper for the browser modules.
 *
 * The dictionary is rendered by Blade in `layouts/partials/i18n.blade.php`.
 * Keys are the English source strings; the server resolves them against the
 * active locale, so the client never has to know which language is active.
 */
export function t(key, replacements = {}) {
    const dictionary = window.i18n || {};
    let text = Object.prototype.hasOwnProperty.call(dictionary, key) ? dictionary[key] : key;

    Object.entries(replacements).forEach(([name, value]) => {
        text = text.split(`:${name}`).join(String(value));
    });

    return text;
}

window.t = t;
