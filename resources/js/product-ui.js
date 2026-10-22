/**
 * Product UI behaviours: live image preview, the category-driven attribute
 * selector, and the import dropzone.
 *
 * Alpine is not a dependency of this project, so everything here is plain DOM
 * work wired up on DOMContentLoaded. Each initialiser is defensive: a read-only
 * visitor gets no create/edit/import controls at all, and the page still loads.
 */

import { t } from './i18n';

const MAX_IMAGE_KILOBYTES = 2048;
const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const ACCEPTED_SPREADSHEET_EXTENSIONS = ['csv', 'xlsx', 'xls'];

/**
 * Swap the stored image for a local preview as soon as a file is chosen, so the
 * user sees the picture they picked before anything is uploaded.
 */
function initImageFields() {
    document.querySelectorAll('[data-image-field]').forEach((root) => {
        const input = root.querySelector('[data-image-input]');
        const preview = root.querySelector('[data-image-preview]');
        const placeholder = root.querySelector('[data-image-placeholder]');
        const message = root.querySelector('[data-image-message]');
        const remove = root.querySelector('[data-image-remove]');

        if (!input || !preview) {
            return;
        }

        const complain = (text) => {
            if (!message) {
                return;
            }

            message.textContent = text;
            message.classList.remove('hidden');
        };

        const clearComplaint = () => message?.classList.add('hidden');

        const show = (src) => {
            preview.src = src;
            preview.classList.remove('hidden');
            placeholder?.classList.add('hidden');
        };

        input.addEventListener('change', () => {
            clearComplaint();
            const file = input.files?.[0];

            if (!file) {
                // The dialog was dismissed; put the stored image back.
                if (remove?.checked) {
                    show('');
                }

                return;
            }

            if (!ACCEPTED_IMAGE_TYPES.includes(file.type)) {
                complain(t('Pick a JPEG, PNG or WebP file.'));
                input.value = '';

                return;
            }

            if (file.size > MAX_IMAGE_KILOBYTES * 1024) {
                complain(t('That image is :size MB. The limit is 2 MB.', { size: Math.round(file.size / 1024 / 1024) }));
                input.value = '';

                return;
            }

            const reader = new FileReader();
            reader.addEventListener('load', () => show(reader.result));
            reader.addEventListener('error', () => complain(t('That file could not be read.')));
            reader.readAsDataURL(file);

            // Choosing a replacement supersedes the pending removal.
            if (remove) {
                remove.checked = false;
            }
        });

        remove?.addEventListener('change', () => {
            if (remove.checked) {
                input.disabled = true;
                input.value = '';
                show('');
            } else {
                input.disabled = false;
            }

            clearComplaint();
        });
    });
}

/**
 * Per-attribute autocomplete tag selector.
 *
 * Each attribute is its own combobox: the values only exist inside a dropdown,
 * never as an expanded checklist, so a category with forty values cannot push
 * the modal past the viewport. Picking a value appends a chip that carries the
 * checkbox which actually posts, so the form contract is unchanged — the server
 * still receives attribute_value_ids[] exactly as before.
 *
 * Keeping the chips is the whole point of the layout: a selection stays visible
 * after the input clears, which is what a mass checklist could not do.
 */
const REMOVE_ICON =
    '<svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">' +
    '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';

const CHIP_CLASS =
    'inline-flex items-center gap-1 rounded-full bg-primary-100 py-0.5 pl-2 pr-1 text-xs font-medium text-primary-900 dark:bg-primary-900/40 dark:text-primary-100';
const REMOVE_CLASS =
    'rounded p-0.5 text-primary-700 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-primary-400 dark:text-primary-200 dark:hover:bg-primary-800';
const OPTION_CLASS =
    'block w-full px-3 py-2 text-left text-sm text-gray-800 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:bg-gray-700';
const ACTIVE_OPTION_CLASS =
    'bg-gray-100 font-medium text-gray-900 dark:bg-gray-700 dark:text-white';
const ACTIVE_OPTION_TOKENS = ACTIVE_OPTION_CLASS.split(' ');

/**
 * The highlighted option is tracked by a data attribute rather than by probing
 * for a utility class, so restyling the highlight cannot silently break
 * keyboard navigation.
 *
 * Tokens are toggled one at a time on purpose. DOMTokenList.toggle() only
 * accepts (token, force), so spreading the whole list plus the flag honours
 * the first token and silently drops the rest - which left a bare bg-gray-100
 * on the option with no dark:bg-gray-700 to counter it, putting light grey
 * behind light text the moment anything was arrow-keyed.
 */
const setActive = (option, active) => {
    ACTIVE_OPTION_TOKENS.forEach((token) => option.classList.toggle(token, active));

    if (active) {
        option.dataset.attributeActive = '';
    } else {
        delete option.dataset.attributeActive;
    }
};

const clearActive = (options) => options.forEach((option) => setActive(option, false));
const activeOf = (options) => options.find((option) => option.dataset.attributeActive !== undefined) || null;

/** Every group on the page, so one document listener can close them all. */
let registeredGroups = [];
let outsideClickBound = false;

function initAttributeSelectors() {
    document.querySelectorAll('[data-attribute-selector]').forEach((root) => {
        const fields = root.querySelector('[data-attribute-fields]');
        const summary = root.querySelector('[data-attribute-summary]');
        const fieldName = fields?.dataset.attributeName || 'attribute_value_ids[]';
        const categorySelect = document.querySelector(
            `select[data-attribute-trigger="${root.dataset.attributeSelector}"]`
        );

        if (!fields) {
            return;
        }

        const payload = () => {
            try {
                return JSON.parse(root.dataset.attributePayload || '[]');
            } catch (e) {
                return [];
            }
        };

        const groupsOf = (scope) => Array.from(scope.querySelectorAll('[data-attribute-group]'));

        const selectedIn = (group) => new Set(
            Array.from(group.querySelectorAll('[data-attribute-chip-input]'))
                .filter((box) => box.checked)
                .map((box) => box.value)
        );

        const totalSelected = () =>
            root.querySelectorAll('[data-attribute-chip-input]:checked').length;

        const updateSummary = () => {
            if (summary) {
                const count = totalSelected();
                summary.textContent = count === 0 ? t('Nothing selected yet') : t(':count selected', { count });
            }

            groupsOf(root).forEach((group) => {
                const counter = group.querySelector('[data-attribute-count]');
                if (counter) {
                    counter.textContent = String(selectedIn(group).size);
                }
            });
        };

        // --- dropdown open/close ------------------------------------------------

        const open = (group) => {
            group.querySelector('[data-attribute-dropdown]')?.classList.remove('hidden');
            group.querySelector('[data-attribute-search]')?.setAttribute('aria-expanded', 'true');
        };

        const close = (group) => {
            group.querySelector('[data-attribute-dropdown]')?.classList.add('hidden');
            group.querySelector('[data-attribute-search]')?.setAttribute('aria-expanded', 'false');
        };

        const isOpen = (group) =>
            !group.querySelector('[data-attribute-dropdown]')?.classList.contains('hidden');

        // --- filtering ---------------------------------------------------------

        /**
         * Show the options that match the query and have not been chosen yet.
         * An empty query lists everything still available, which is what makes
         * the combobox usable on focus without typing first.
         */
        const refresh = (group) => {
            const input = group.querySelector('[data-attribute-search]');
            const dropdown = group.querySelector('[data-attribute-dropdown]');
            const noMatch = group.querySelector('[data-attribute-no-match]');

            if (!input || !dropdown) {
                return 0;
            }

            const chosen = selectedIn(group);
            const query = input.value.trim().toLowerCase();
            let shown = 0;

            dropdown.querySelectorAll('[data-attribute-option]').forEach((option) => {
                const label = (option.dataset.valueLabel || '').toLowerCase();
                const available = !chosen.has(option.dataset.valueId);
                const matches = available && (query === '' || label.includes(query));
                const row = option.closest('li');

                row?.classList.toggle('hidden', !matches);
                setActive(option, false);

                if (matches) {
                    shown++;
                }
            });

            // "No matches" only makes sense once the user has asked for
            // something; a blank box with everything chosen is a different state.
            if (noMatch) {
                noMatch.classList.toggle('hidden', shown > 0 || query === '');
            }

            return shown;
        };

        const refreshAll = () => groupsOf(root).forEach(refresh);

        // --- chips -------------------------------------------------------------

        const addChip = (group, option) => {
            const id = option.dataset.valueId;
            const label = option.dataset.valueLabel;

            // The dropdown already hides chosen values; this guards the race
            // where a stale click arrives after the row was hidden.
            if (selectedIn(group).has(id)) {
                return;
            }

            const chip = document.createElement('span');
            chip.className = CHIP_CLASS;
            chip.dataset.attributeChip = '';

            // The checkbox is the form field, so it lives inside a label whose
            // text is the chip label. Keeping the label and the remove button as
            // siblings avoids a button nested in a label, which would toggle the
            // checkbox when the button is clicked.
            const fieldLabel = document.createElement('label');
            fieldLabel.className = 'flex cursor-pointer items-center gap-1';

            const box = document.createElement('input');
            box.type = 'checkbox';
            box.name = fieldName;
            box.value = id;
            box.checked = true;
            box.dataset.attributeChipInput = '';
            box.className = 'sr-only';

            const text = document.createElement('span');
            text.textContent = label;

            fieldLabel.append(box, text);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.attributeChipRemove = '';
            remove.setAttribute('aria-label', t('Remove :label', { label }));
            remove.className = REMOVE_CLASS;
            remove.innerHTML = REMOVE_ICON;

            chip.append(fieldLabel, remove);
            group.querySelector('[data-attribute-tags]')?.append(chip);

            const input = group.querySelector('[data-attribute-search]');
            if (input) {
                input.value = '';
            }

            refresh(group);
            updateSummary();
            close(group);
        };

        const removeChip = (chip) => {
            chip.remove();
            refresh(chip.closest('[data-attribute-group]'));
            updateSummary();
        };

        // --- options list ------------------------------------------------------

        const buildOption = (value) => {
            const row = document.createElement('li');
            row.setAttribute('role', 'none');

            const option = document.createElement('button');
            option.type = 'button';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.dataset.attributeOption = '';
            option.dataset.valueId = String(value.id);
            option.dataset.valueLabel = value.label;
            option.className = OPTION_CLASS;
            option.textContent = value.label;

            row.append(option);
            return row;
        };

        /**
         * Replace a group's options in place, keeping the field chrome and any
         * chips already chosen. This is what a category change runs, so scoping
         * attributes per category becomes a controller change rather than a
         * markup change.
         */
        const rebuildOptions = (group, attribute) => {
            const dropdown = group.querySelector('[data-attribute-dropdown]');
            if (!dropdown) {
                return;
            }

            const noMatch = dropdown.querySelector('[data-attribute-no-match]');

            dropdown.querySelectorAll('[data-attribute-option]').forEach((option) => {
                option.closest('li')?.remove();
            });

            const ordered = [...attribute.values].sort((a, b) =>
                a.label.localeCompare(b.label, undefined, { sensitivity: 'base' })
            );

            ordered.forEach((value) => {
                dropdown.insertBefore(buildOption(value), noMatch);
            });
        };

        // --- keyboard ----------------------------------------------------------

        const visibleOptions = (group) =>
            Array.from(group.querySelectorAll('[data-attribute-option]'))
                .filter((option) => !option.closest('li')?.classList.contains('hidden'));

        const moveActive = (group, step) => {
            const options = visibleOptions(group);
            if (options.length === 0) {
                return;
            }

            const current = options.findIndex((option) =>
                option.dataset.attributeActive !== undefined
            );
            const next = Math.min(Math.max(current + step, 0), options.length - 1);

            clearActive(options);
            setActive(options[next], true);
            options[next].scrollIntoView({ block: 'nearest' });
        };

        // --- wiring ------------------------------------------------------------

        const wire = (group) => {
            const input = group.querySelector('[data-attribute-search]');
            const dropdown = group.querySelector('[data-attribute-dropdown]');

            if (!input || !dropdown) {
                return;
            }

            input.addEventListener('input', () => {
                // A non-empty query that matches nothing still opens, so the
                // "No matches" row can explain itself. A blank query with
                // nothing left to offer closes rather than hanging open empty.
                const shown = refresh(group);

                if (shown > 0 || input.value.trim() !== '') {
                    open(group);
                } else {
                    close(group);
                }
            });

            input.addEventListener('focus', () => {
                refresh(group);
                open(group);
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    if (!isOpen(group)) {
                        refresh(group);
                        open(group);
                    }
                    moveActive(group, 1);
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    moveActive(group, -1);
                    return;
                }

                if (event.key === 'Enter') {
                    // Enter must never submit the product form from here.
                    event.preventDefault();
                    const options = visibleOptions(group);
                    const active = activeOf(options);

                    if (active) {
                        addChip(group, active);
                    } else if (options.length === 1) {
                        addChip(group, options[0]);
                    }

                    return;
                }

                if (event.key === 'Escape') {
                    // Swallow it while open so it does not also bubble out and
                    // close a parent Flowbite modal.
                    event.preventDefault();
                    event.stopPropagation();
                    close(group);
                }
            });

            dropdown.addEventListener('click', (event) => {
                const option = event.target.closest('[data-attribute-option]');
                if (option) {
                    addChip(group, option);
                }
            });

            group.addEventListener('click', (event) => {
                const button = event.target.closest('[data-attribute-chip-remove]');
                if (button) {
                    button.closest('[data-attribute-chip]')?.remove();
                    refresh(group);
                    updateSummary();
                }
            });
        };

        groupsOf(root).forEach((group) => {
            wire(group);
            registeredGroups.push({ group, refresh, close });
        });

        // Initial chips are rendered by Blade from the record's existing values,
        // so there is nothing to reconstruct here — only the counts to refresh.
        updateSummary();
        refreshAll();

        // A category change repopulates each group's options in place, so chips
        // the user already picked survive the switch.
        categorySelect?.addEventListener('change', () => {
            const byId = new Map(payload().map((attribute) => [String(attribute.id), attribute]));

            groupsOf(root).forEach((group) => {
                const attribute = byId.get(String(group.dataset.attributeGroup));
                if (attribute) {
                    rebuildOptions(group, attribute);
                }
            });

            refreshAll();
            updateSummary();
        });

        if (!outsideClickBound && registeredGroups.length > 0) {
            outsideClickBound = true;

            // One listener for every combobox on the page; N modals would
            // otherwise register N document listeners.
            document.addEventListener('click', (event) => {
                registeredGroups.forEach(({ group, close }) => {
                    const combobox = group.querySelector('[data-attribute-combobox]');
                    if (combobox && !combobox.contains(event.target)) {
                        close(group);
                    }
                });
            });
        }
    });
}

/**
 * Drag-and-drop wrapper around a real file input. The input stays the source of
 * truth — a drop only populates it — so submission works with or without drag.
 */
function initDropzones() {
    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = document.querySelector(`input[type="file"][data-dropzone-input="${zone.dataset.dropzone}"]`);
        const message = zone.querySelector('[data-dropzone-message]');
        const filename = zone.querySelector('[data-dropzone-filename]');
        const idle = zone.querySelector('[data-dropzone-idle]');

        if (!input) {
            return;
        }

        const complain = (text) => {
            if (!message) {
                return;
            }

            message.textContent = text;
            message.classList.remove('hidden');
        };

        const clearComplaint = () => message?.classList.add('hidden');

        const describe = (file) => {
            const extension = (file.name.split('.').pop() || '').toLowerCase();

            if (!ACCEPTED_SPREADSHEET_EXTENSIONS.includes(extension)) {
                complain(t('Only .csv, .xlsx and .xls files are accepted.'));
                input.value = '';

                return;
            }

            clearComplaint();

            if (filename) {
                filename.textContent = file.name;
            }

            idle?.classList.add('hidden');
        };

        input.addEventListener('change', () => {
            clearComplaint();
            const file = input.files?.[0];

            if (file) {
                describe(file);
            }
        });

        ['dragenter', 'dragover'].forEach((type) => {
            zone.addEventListener(type, (event) => {
                event.preventDefault();
                zone.classList.add('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/20');
            });
        });

        ['dragleave', 'drop'].forEach((type) => {
            zone.addEventListener(type, () => {
                zone.classList.remove('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/20');
            });
        });

        zone.addEventListener('drop', (event) => {
            event.preventDefault();
            const file = event.dataTransfer?.files?.[0];

            if (!file) {
                return;
            }

            // DataTransfer is the only way to set a file input's files.
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            describe(file);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initImageFields();
    initAttributeSelectors();
    initDropzones();
});