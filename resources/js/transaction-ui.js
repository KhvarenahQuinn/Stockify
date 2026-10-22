/**
 * Movement form behaviours: the product combobox, the live availability check
 * that guards a goods issue against over-allocation, and the physical count
 * read-out inside the inspection modal.
 *
 * Alpine is not a dependency of this project, so everything here is plain DOM
 * work wired up on DOMContentLoaded, matching product-ui.js. Every initialiser
 * is defensive: a manager reading the ledger gets none of these controls and
 * the page still loads.
 */

import { t } from './i18n';

const EVENT_PRODUCT_SELECTED = 'stockify:product-selected';
const EVENT_PRODUCT_CLEARED = 'stockify:product-cleared';

const numberFormat = new Intl.NumberFormat(document.documentElement.lang || 'en-US');

/** Remove every token in `tokens` from `element`, whatever its current state. */
const dropClasses = (element, tokens) => {
    tokens.forEach((token) => element.classList.remove(token));
};

const OPTION_ACTIVE_TOKENS = [
    'bg-gray-100',
    'dark:bg-gray-600',
];

/**
 * The highlighted option is tracked by a data attribute rather than by probing
 * for a utility class, so restyling the highlight cannot silently break
 * keyboard navigation.
 */
const setActiveOption = (option, active) => {
    OPTION_ACTIVE_TOKENS.forEach((token) => option.classList.toggle(token, active));

    if (active) {
        option.dataset.productActive = '';
    } else {
        delete option.dataset.productActive;
    }
};

/** Build a CustomEvent that survives bubbling out to a form-level listener. */
const productEvent = (name, detail) =>
    new CustomEvent(name, { detail, bubbles: true });

const CHIP_BADGE_CLASS =
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold';
const CHIP_BADGE_DOT =
    'h-1.5 w-1.5 shrink-0 rounded-full bg-white';

/**
 * Repaint the chip's stock badge after a pick.
 *
 * The tone map is handed down from Blade rather than hard-coded here, so the
 * client and the server can never disagree about what "low stock" looks like —
 * and, more importantly, never disagree about whether a product is low at all.
 */
const paintChipBadge = (host, stock, minStock) => {
    let tones;

    try {
        tones = JSON.parse(host.dataset.stockTones || '{}');
    } catch (e) {
        tones = {};
    }

    const entries = Object.keys(tones);
    const level = stock <= 0 ? 'out' : stock <= minStock ? 'low' : 'safe';
    const state = tones[level] || entries[0];

    if (!state) {
        return;
    }

    const holder = host.querySelector('[data-product-chip-level]');

    if (!holder) {
        return;
    }

    const badge = document.createElement('span');
    badge.className = `${CHIP_BADGE_CLASS} ${state.tone}`;

    const dot = document.createElement('span');
    dot.className = CHIP_BADGE_DOT;
    dot.setAttribute('aria-hidden', 'true');

    badge.append(dot, document.createTextNode(state.label));
    holder.replaceChildren(badge);
};

/**
 * Product search over the catalogue, posting a plain hidden input.
 *
 * The list is rendered server-side and filtered here rather than fetched, so the
 * first keystroke is instant and the box still works with JavaScript disabled:
 * without JS the buttons submit their id through a hidden field only after a
 * pick, and the page degrades to "type a name, then nothing" rather than
 * breaking. That is the same trade product-ui.js already makes.
 */
function initProductComboboxes() {
    document.querySelectorAll('[data-product-combobox]').forEach((root) => {
        const search = root.querySelector('[data-product-search]');
        const value = root.querySelector('[data-product-value]');
        const listbox = root.querySelector('[data-product-listbox]');
        const empty = root.querySelector('[data-product-empty]');
        const clearButton = root.querySelector('[data-product-clear]');
        const announcer = root.querySelector('[data-product-count]');
        const chip = root.querySelector('[data-product-chip]');
        const chipName = root.querySelector('[data-product-chip-name]');
        const chipSku = root.querySelector('[data-product-chip-sku]');
        const chipStock = root.querySelector('[data-product-chip-stock]');

        if (!search || !value || !listbox) {
            return;
        }

        const rows = Array.from(listbox.querySelectorAll('[data-product-row]'));
        const optionsOf = (row) => row.querySelector('[data-product-option]');

        const selected = () =>
            rows
                .map(optionsOf)
                .find((option) => option && option.dataset.id === value.value) || null;

        const visibleOptions = () =>
            rows.filter((row) => !row.classList.contains('hidden')).map(optionsOf);

        const activeOption = () =>
            visibleOptions().find((option) => option.dataset.productActive !== undefined) || null;

        const moveActive = (step) => {
            const options = visibleOptions();

            if (options.length === 0) {
                return;
            }

            const current = options.findIndex(
                (option) => option.dataset.productActive !== undefined
            );
            const next = Math.min(Math.max(current + step, 0), options.length - 1);

            visibleOptions().forEach((option) => setActiveOption(option, false));
            setActiveOption(options[next], true);
            options[next].scrollIntoView({ block: 'nearest' });
            search.setAttribute('aria-activedescendant', options[next].id);
        };

        const open = () => {
            listbox.classList.remove('hidden');
            search.setAttribute('aria-expanded', 'true');
        };

        const close = () => {
            listbox.classList.add('hidden');
            search.setAttribute('aria-expanded', 'false');
            search.removeAttribute('aria-activedescendant');
            visibleOptions().forEach((option) => setActiveOption(option, false));
        };

        const isOpen = () => !listbox.classList.contains('hidden');

        /**
         * Match on name or SKU. An empty query lists everything, which is what
         * makes the box useful on focus without typing first.
         */
        const refresh = () => {
            const query = search.value.trim().toLowerCase();
            let shown = 0;

            rows.forEach((row) => {
                const option = optionsOf(row);
                const name = (option?.dataset.name || '').toLowerCase();
                const sku = (option?.dataset.sku || '').toLowerCase();
                const matches = query === '' || name.includes(query) || sku.includes(query);

                row.classList.toggle('hidden', !matches);
                setActiveOption(option, false);

                if (matches) {
                    shown++;
                }
            });

            // "No matches" only makes sense once the user has asked for
            // something. A blank box with everything filtered out is a different
            // state and would read as a broken list.
            empty?.classList.toggle('hidden', shown > 0 || query === '');

            if (announcer) {
                announcer.textContent =
                    shown === 0
                        ? t('No products match.')
                        : t(shown === 1 ? ':count product available.' : ':count products available.', { count: shown });
            }

            return shown;
        };

        const choose = (option) => {
            if (!option) {
                return;
            }

            const { id, name, sku, stock, unit, minStock } = option.dataset;

            value.value = id;
            search.value = name;
            search.setAttribute('aria-invalid', 'false');

            if (chipName) chipName.textContent = name;
            if (chipSku) chipSku.textContent = sku;
            if (chipStock) chipStock.textContent = ` · ${numberFormat.format(Number(stock))} ${unit} ${t('on hand')}`;
            chip?.classList.remove('hidden');
            clearButton?.classList.remove('hidden');

            if (chip) {
                paintChipBadge(chip, Number(stock), Number(minStock || 0));
            }

            root.dispatchEvent(
                productEvent(EVENT_PRODUCT_SELECTED, {
                    id,
                    name,
                    sku,
                    stock: Number(stock),
                    unit,
                    minStock: Number(minStock || 0),
                })
            );

            close();
        };

        const clear = () => {
            value.value = '';
            search.value = '';
            search.removeAttribute('aria-invalid');
            chip?.classList.add('hidden');
            clearButton?.classList.add('hidden');

            root.dispatchEvent(productEvent(EVENT_PRODUCT_CLEARED, {}));
        };

        search.addEventListener('input', () => {
            // Typing after a pick means the old hidden value no longer describes
            // what is on screen. Dropping it is safer than silently submitting a
            // product the operator has stopped looking at.
            if (selected() && search.value.trim().toLowerCase() !==
                (selected().dataset.name || '').toLowerCase()) {
                value.value = '';
                chip?.classList.add('hidden');
                root.dispatchEvent(productEvent(EVENT_PRODUCT_CLEARED, {}));
            }

            const shown = refresh();

            if (shown > 0 || search.value.trim() !== '') {
                open();
            } else {
                close();
            }
        });

        search.addEventListener('focus', () => {
            refresh();
            open();
        });

        search.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (!isOpen()) {
                    refresh();
                    open();
                }
                moveActive(1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveActive(-1);
                return;
            }

            if (event.key === 'Enter') {
                // Enter must never submit the movement form from inside here.
                event.preventDefault();
                const active = activeOption();
                const visible = visibleOptions();

                if (active) {
                    choose(active);
                } else if (visible.length === 1) {
                    choose(visible[0]);
                }

                return;
            }

            if (event.key === 'Escape') {
                // Swallow it while open so it does not also bubble out and
                // close a parent Flowbite modal.
                event.preventDefault();
                event.stopPropagation();
                close();
            }
        });

        listbox.addEventListener('mousedown', (event) => {
            // Keep focus in the box: blur fires before click on some browsers,
            // which would close the list out from under the click.
            event.preventDefault();
        });

        listbox.addEventListener('click', (event) => {
            const option = event.target.closest('[data-product-option]');
            if (option) {
                choose(option);
                search.focus();
            }
        });

        clearButton?.addEventListener('click', () => {
            clear();
            search.focus();
        });

        // One listener per combobox is fine here; a page has at most one movement
        // form and the delegation below is scoped to this root's listbox.
        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                close();
            }
        });

        // A page that re-renders after a validation redirect can leave the box
        // focused with no dropdown behind it; re-assert the resting state.
        refresh();
        close();
        clearButton?.classList.toggle('hidden', !value.value);
    });
}

/**
 * The availability panel on the movement form.
 *
 * Reads the same [name="quantity"] the server will validate, so the warning and
 * the eventual rule can never disagree about which number is being judged.
 */
/**
 * Visibility is toggled with the `.hidden` class, not the HTML attribute.
 *
 * Preflight's `[hidden] { display: none }` lives in the base layer and every
 * `display` utility lives in the utilities layer above it, so a `flex` class on
 * the same element beats the attribute and the panel stays on screen. Within
 * the utilities layer `hidden` is emitted last, so the class wins over `flex` —
 * which is what makes the callouts collapsible at all.
 */
const toggle = (element, visible) => {
    element?.classList.toggle('hidden', !visible);
};

function initStockChecks() {
    document.querySelectorAll('[data-stock-check]').forEach((panel) => {
        const form = panel.closest('[data-movement-form]') || document;
        const emptyNote = panel.querySelector('[data-stock-empty]');
        const figures = panel.querySelector('[data-stock-figures]');
        const nameOut = panel.querySelector('[data-stock-name]');
        const skuOut = panel.querySelector('[data-stock-sku]');
        const availableOut = panel.querySelector('[data-stock-available]');
        const projectedOut = panel.querySelector('[data-stock-projected]');
        const deltaOut = panel.querySelector('[data-stock-delta]');
        const warning = panel.querySelector('[data-stock-warning]');
        const warningText = panel.querySelector('[data-stock-warning-text]');
        const note = panel.querySelector('[data-stock-note]');
        const noteText = panel.querySelector('[data-stock-note-text]');
        const directionOut = panel.querySelector('[data-stock-direction]');

        const quantityInput = form.querySelector('[name="quantity"]');

        let product = null;

        const currentType = () => {
            const checked = form.querySelector('[name="type"]:checked');
            return (checked?.value || 'IN').toUpperCase();
        };

        const currentQuantity = () => {
            const raw = parseInt(quantityInput?.value ?? '', 10);
            return Number.isFinite(raw) && raw > 0 ? raw : 0;
        };

        /**
         * Show or hide one of the two callouts.
         *
         * The copy goes into the inner span rather than the wrapper on purpose:
         * writing textContent on the wrapper would replace its children, so the
         * first message would delete the very node the next one is written into.
         */
        const callout = (wrapper, target, message) => {
            if (!wrapper || !target) {
                return;
            }

            toggle(wrapper, Boolean(message));
            target.textContent = message || '';
        };

        const render = () => {
            if (!product) {
                toggle(emptyNote, true);
                toggle(figures, false);
                callout(warning, warningText, '');
                callout(note, noteText, '');
                quantityInput?.setCustomValidity('');

                return;
            }

            toggle(emptyNote, false);
            toggle(figures, true);

            const isInbound = currentType() === 'IN';
            const quantity = currentQuantity();
            const projected = isInbound
                ? product.stock + quantity
                : product.stock - quantity;
            const overAllocated = !isInbound && quantity > product.stock;

            // Rendered server-side for the no-script case, but it describes the
            // direction that is showing, so it has to follow the radios.
            if (directionOut) {
                directionOut.textContent = t(isInbound
                    ? 'This will increase stock on hand.'
                    : 'This will reduce stock on hand.');
            }

            if (nameOut) nameOut.textContent = product.name;
            if (skuOut) skuOut.textContent = product.sku;
            if (availableOut) availableOut.textContent = numberFormat.format(product.stock);
            if (projectedOut) projectedOut.textContent = numberFormat.format(projected);

            // Only reachable while someone is still typing their way up to a
            // valid figure. A negative projection is impossible to submit, so
            // it is tinted rather than hidden — it reads as the hole they are
            // about to fall into, next to the warning that explains it.
            const negative = projected < 0;
            const projectionTone = negative
                ? ['bg-red-200', 'text-red-900', 'dark:bg-red-800', 'dark:text-red-100']
                : ['bg-gray-200', 'text-gray-900', 'dark:bg-gray-500', 'dark:text-white'];

            ['bg-gray-200', 'text-gray-900', 'dark:bg-gray-500', 'dark:text-white'].forEach((token) => {
                projectedOut?.parentElement?.classList.remove(token);
            });
            projectionTone.forEach((token) => {
                projectedOut?.parentElement?.classList.add(token);
            });

            if (deltaOut) {
                const delta = quantity * (isInbound ? 1 : -1);
                const sign = delta > 0 ? '+' : '';

                deltaOut.textContent = quantity > 0 ? `${sign}${numberFormat.format(delta)}` : '';
                deltaOut.classList.toggle(
                    'text-green-700',
                    isInbound && quantity > 0
                );
                deltaOut.classList.toggle('dark:text-green-400', isInbound && quantity > 0);
                deltaOut.classList.toggle(
                    'text-red-700',
                    !isInbound && quantity > 0
                );
                deltaOut.classList.toggle('dark:text-red-400', !isInbound && quantity > 0);
                deltaOut.classList.toggle('text-gray-600', quantity === 0);
                deltaOut.classList.toggle('dark:text-gray-300', quantity === 0);
            }

            // The warning is the one place in the form that talks in a solid red
            // fill, so it matches the severity of a rejected movement badge
            // rather than the softer tints used for hints.
            callout(
                warning,
                warningText,
                overAllocated
                    ? t('Only :available :unit on hand - :short short of the :requested requested. Lower the quantity or pick another product.', {
                        available: numberFormat.format(product.stock),
                        unit: product.unit,
                        short: numberFormat.format(quantity - product.stock),
                        requested: numberFormat.format(quantity),
                    })
                    : ''
            );

            // A hint, not a warning: the shelf is already at its reorder point,
            // which is a purchasing problem rather than a problem with this form.
            callout(
                note,
                noteText,
                !overAllocated && product.stock > 0 && product.stock <= product.minStock
                    ? t(':available :unit on hand is at or below the :min reorder point for this product.', {
                        available: numberFormat.format(product.stock),
                        unit: product.unit,
                        min: numberFormat.format(product.minStock),
                    })
                    : ''
            );

            if (quantityInput) {
                quantityInput.setCustomValidity(
                    overAllocated
                        ? t('Only :available :unit available.', {
                            available: numberFormat.format(product.stock),
                            unit: product.unit,
                        })
                        : ''
                );
                quantityInput.setAttribute('aria-invalid', overAllocated ? 'true' : 'false');

                // Only the border changes; the rest of the control keeps the
                // shared form styling so an error never restyles the field.
                quantityInput.classList.toggle('border-red-500', overAllocated);
                quantityInput.classList.toggle('focus:border-red-500', overAllocated);
                quantityInput.classList.toggle('focus:ring-red-500', overAllocated);
                quantityInput.classList.toggle('dark:border-red-500', overAllocated);
                quantityInput.classList.toggle('border-gray-300', !overAllocated);
                quantityInput.classList.toggle('dark:border-gray-600', !overAllocated);
                quantityInput.classList.toggle('focus:border-primary-500', !overAllocated);
                quantityInput.classList.toggle('focus:ring-primary-500', !overAllocated);
            }
        };

        form.addEventListener(EVENT_PRODUCT_SELECTED, (event) => {
            product = event.detail;
            render();
        });

        form.addEventListener(EVENT_PRODUCT_CLEARED, () => {
            product = null;
            render();
        });

        form.addEventListener('change', (event) => {
            if (event.target?.name === 'type') {
                render();
            }
        });

        form.addEventListener('input', (event) => {
            if (event.target === quantityInput) {
                render();
            }
        });

        // A restore from old input can land with a product already chosen but no
        // event ever fired, so the panel adopts whatever the combobox holds.
        const initialOption = Array.from(
            document.querySelectorAll('[data-product-option]')
        ).find((option) =>
            option.dataset.id === form.querySelector('[data-product-value]')?.value
        );

        if (initialOption) {
            form.dispatchEvent(
                productEvent(EVENT_PRODUCT_SELECTED, {
                    id: initialOption.dataset.id,
                    name: initialOption.dataset.name,
                    sku: initialOption.dataset.sku,
                    stock: Number(initialOption.dataset.stock),
                    unit: initialOption.dataset.unit,
                    minStock: Number(initialOption.dataset.minStock || 0),
                })
            );
        } else {
            render();
        }
    });
}

/**
 * The count read-out inside the inspection modal.
 *
 * The stored quantity always decides whether a discrepancy exists, server-side.
 * This only mirrors that arithmetic so the operator reads the consequence before
 * committing to it — it never becomes a second source of truth.
 */
function initInspectForms() {
    document.querySelectorAll('[data-inspect-form]').forEach((root) => {
        const quantity = root.querySelector('[data-inspect-quantity]');
        const outcome = root.querySelector('[data-inspect-outcome]');
        const outcomeText = root.querySelector('[data-inspect-outcome-text]');
        const note = root.querySelector('[data-inspect-note]');

        // The submit button lives in the modal footer, outside the read-out
        // block, so it is reached through the modal's own form rather than by
        // searching root — which would come back null and take the rest of
        // render() down with it.
        const owner = root.closest('form') || document;
        const submit = owner.querySelector('[data-inspect-submit]');

        if (!quantity || !outcome || !submit) {
            return;
        }

        const requested = parseInt(root.dataset.requested || '0', 10);
        const unit = root.dataset.unit || 'unit';

        const MATCH_TOKENS = [
            'border-green-200',
            'bg-green-50',
            'text-green-900',
            'dark:border-green-800',
            'dark:bg-green-900/40',
            'dark:text-green-100',
        ];
        const MISMATCH_TOKENS = [
            'border-red-200',
            'bg-red-50',
            'text-red-900',
            'dark:border-red-800',
            'dark:bg-red-900/40',
            'dark:text-red-100',
        ];
        const PRIMARY_TOKENS = ['bg-primary-700', 'hover:bg-primary-800', 'focus:ring-primary-300'];
        const DANGER_TOKENS = ['bg-red-700', 'hover:bg-red-800', 'focus:ring-red-300'];

        const restyle = (element, tokens) => {
            dropClasses(element, [...MATCH_TOKENS, ...MISMATCH_TOKENS]);
            element.classList.add(...tokens);
        };

        const render = () => {
            const counted = parseInt(quantity.value ?? '', 10);
            const known = Number.isFinite(counted) && quantity.value.trim() !== '';
            const variance = known ? counted - requested : 0;
            const matches = known && variance === 0;

            restyle(outcome, matches ? MATCH_TOKENS : MISMATCH_TOKENS);

            if (!known) {
                outcomeText.textContent = t('Enter what is physically on the shelf.');
            } else if (matches) {
                outcomeText.textContent = t('Count matches the request. This goes to a manager for approval as :requested :unit.', {
                    requested,
                    unit,
                });
            } else {
                const direction = variance < 0 ? t('short of') : t('over');

                outcomeText.textContent = t('You counted :counted :unit, :variance :direction the requested :requested. A manager decides what happens next.', {
                    counted,
                    unit,
                    variance: Math.abs(variance),
                    direction,
                    requested,
                });
            }

            dropClasses(submit, [...PRIMARY_TOKENS, ...DANGER_TOKENS]);
            submit.classList.add(...(matches ? PRIMARY_TOKENS : DANGER_TOKENS));

            submit.textContent = !known
                ? t('Record the count')
                : matches
                ? t('Confirm count and send to a manager')
                : t('Flag discrepancy and send to a manager');

            // A mismatch without a note is a mystery to whoever reads it next,
            // so the field becomes mandatory at exactly the moment it matters.
            if (note) {
                note.required = known && !matches;
                note.setCustomValidity(
                    known && !matches && note.value.trim() === ''
                        ? t('Say what you found before flagging a discrepancy.')
                        : ''
                );
            }
        };

        quantity.addEventListener('input', render);
        note?.addEventListener('input', () => render());
        render();
    });
}

/**
 * Show only the counterparty that applies to the chosen direction.
 *
 * Both fields ship in the HTML so the form still submits with JavaScript off —
 * the server re-checks which one is required, so the hidden one being filled
 * costs nothing. With scripting on, showing both at once only invites someone
 * to fill in the field that is about to be ignored.
 */
function initDirectionFields() {
    const groups = Array.from(document.querySelectorAll('[data-direction-only]'));

    if (groups.length === 0) {
        return;
    }

    const form = groups[0].closest('[data-movement-form]') || document;

    const apply = () => {
        const checked = form.querySelector('[name="type"]:checked');
        const type = (checked?.value || 'IN').toUpperCase();

        groups.forEach((group) => {
            const applies = group.dataset.directionOnly === type;

            group.classList.toggle('hidden', !applies);

            // Disabling keeps a hidden required field from silently blocking
            // submit with "please fill this in" on something nobody can see.
            group.querySelectorAll('input, select, textarea').forEach((field) => {
                field.disabled = !applies;
            });
        });
    };

    form.addEventListener('change', (event) => {
        if (event.target?.name === 'type') {
            apply();
        }
    });

    apply();
}

/**
 * Name the file the operator just attached, so it is obvious the upload landed
 * before the page navigates away.
 */
function initDocumentUploads() {
    document.querySelectorAll('[data-document-upload]').forEach((root) => {
        const input = root.querySelector('[data-document-input]');
        const message = root.querySelector('[data-document-message]');

        if (!input) {
            return;
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!message) {
                return;
            }

            if (!file) {
                message.classList.add('hidden');
                return;
            }

            // Browsers only enforce the size limit once the server sees the
            // upload. Catching it here saves the round trip and the 422.
            const limitBytes = Number(input.dataset.maxKilobytes || 0) * 1024;

            if (limitBytes && file.size > limitBytes) {
                message.textContent = t('That file is :size MB. The limit is :limit MB.', {
                    size: Math.round(file.size / 1024 / 1024),
                    limit: Math.round(limitBytes / 1024 / 1024),
                });
                message.classList.remove('hidden');
                input.value = '';
                input.setCustomValidity(message.textContent);

                return;
            }

            input.setCustomValidity('');
            message.classList.add('hidden');
        });
    });
}

/**
 * Reopen the modal a failed submission came from.
 *
 * Every form carries a hidden `_form` key holding its own modal id, and
 * Laravel flashes that back as old input. The id is read from a single
 * `[data-modal-restore]` marker rather than from `document.querySelector` on the
 * first `_form` input in the page: a queue renders an approve and a reject
 * dialog for every row, so the first one on the page is almost never the one
 * that failed, and the manager was dropped into someone else's transaction.
 */
function initModalRestore() {
    // Flowbite wires its data attributes on load only, so a validation redirect
    // has to re-init before reopening the form that owns the errors.
    if (typeof window.initFlowbite === 'function') {
        window.initFlowbite();
    }

    // Every marker is scanned, not just the first. The approvals page carries
    // one for a failed submission and another for the manager dashboard's
    // ?review= deep link; taking only the first meant the deep link was
    // silently ignored whenever the validation marker happened to be empty.
    // The first marker with a usable value wins, so whichever arrived from a
    // redirect is the one that opens.
    const markers = Array.from(document.querySelectorAll('[data-modal-restore]'));
    const modalId = markers
        .map((marker) => marker.dataset.modalRestore)
        .find((id) => Boolean(id));

    if (!modalId) {
        return;
    }

    document.querySelector(`[data-modal-toggle="${CSS.escape(modalId)}"]`)?.click();
}

document.addEventListener('DOMContentLoaded', () => {
    initProductComboboxes();
    initStockChecks();
    initInspectForms();
    initDirectionFields();
    initDocumentUploads();
    initModalRestore();
});