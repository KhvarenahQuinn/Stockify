/**
 * Stock opname audit sheet.
 *
 * The variance shown here is a preview only. On submit the server re-reads
 * system_stock from the product row and derives the variance itself, so a
 * tampered figure in the DOM changes nothing.
 *
 * One invariant is load-bearing and is enforced by a test: the three semantic
 * fills - green for matched, blue for surplus, red for deficit - exist only in
 * Blade. The script clones a server-rendered <template> per variant and writes
 * nothing but the number and the word, so a badge can never render in a colour
 * the server would not have produced. Those fills also carry the WCAG AA
 * contrast guarantee, which is why they are not duplicated here.
 *
 * Neutral chrome (the picker's Add button, the "Not counted" pill) is built
 * here because only the client ever renders it - there is no server-side
 * counterpart to drift from. Tailwind's content globs cover this folder, so
 * those utilities are generated like any other.
 */

/** Number formatting that matches Blade's number_format(). */
import { t } from './i18n';

const format = (value) => Math.abs(value).toLocaleString(document.documentElement.lang || 'en-US');

/**
 * Signed figure for a difference column.
 *
 * format() drops the sign because the column may want a magnitude. Here the
 * sign is the whole point: a shortfall of six shown as "6" in a Difference
 * column reads as a surplus of six.
 */
const signed = (value) => `${value > 0 ? '+' : value < 0 ? '-' : ''}${format(value)}`;

function initOpnameSheet() {
    const root = document.querySelector('[data-opname-sheet]');
    if (!root) {
        return;
    }

    const form = root.querySelector('[data-opname-form]');
    const tbody = root.querySelector('[data-opname-rows]');
    const emptyState = root.querySelector('[data-opname-empty]');
    const rowTemplate = root.querySelector('[data-opname-row-template]');
    const search = root.querySelector('[data-opname-search]');
    const categoryFilter = root.querySelector('[data-opname-category]');
    const results = root.querySelector('[data-opname-results]');
    const resultsCount = root.querySelector('[data-opname-results-count]');
    const confirmModal = document.getElementById(root.dataset.opnameConfirm || 'confirm-opname-modal');
    const confirmBody = document.getElementById('confirm-opname-breakdown');
    const confirmTotals = document.getElementById('confirm-opname-totals');
    const alert = root.querySelector('[data-opname-alert]');

    if (!form || !tbody || !rowTemplate) {
        return;
    }

    /* ---- templates --------------------------------------------------- */

    const badgeTemplates = {};
    root.querySelectorAll('[data-opname-badge-template]').forEach((template) => {
        badgeTemplates[template.dataset.opnameBadgeTemplate] = template;
    });

    /**
     * Build a variance chip by cloning the Blade-rendered variant.
     *
     * @param {number} variance
     * @returns {Element|null}
     */
    const badgeFor = (variance) => {
        const kind = variance > 0 ? 'surplus' : variance < 0 ? 'deficit' : 'matched';
        const template = badgeTemplates[kind];

        if (!template) {
            return null;
        }

        const chip = template.content.firstElementChild.cloneNode(true);
        const figure = chip.querySelector('[data-variance-figure]');
        const word = chip.querySelector('[data-variance-word]');

        if (figure) {
            figure.textContent = signed(variance);
        }

        if (word) {
            word.textContent = t(kind === 'surplus' ? 'found' : 'short');
        }

        return chip;
    };

    /* ---- catalogue --------------------------------------------------- */

    let catalogue = [];
    try {
        catalogue = JSON.parse(root.dataset.opnameCatalogue || '[]');
    } catch (e) {
        catalogue = [];
    }

    const added = () => new Set(
        Array.from(tbody.querySelectorAll('[data-product-id]'))
            .map((cell) => cell.dataset.productId)
    );

    /* ---- rows -------------------------------------------------------- */

    const physicalInput = (row) => row.querySelector('[data-physical-input]');

    /**
     * Recompute one row's variance and repaint its badge.
     */
    const recalcRow = (row) => {
        const system = Number(row.dataset.systemStock || 0);
        const field = physicalInput(row);
        const raw = (field?.value ?? '').trim();
        const counted = raw !== '' && Number.isFinite(Number(raw)) && Number(raw) >= 0;

        row.dataset.counted = counted ? '1' : '0';
        row.dataset.variance = counted ? String(Number(raw) - system) : '';

        const slot = row.querySelector('[data-variance-slot]');
        if (!slot) {
            return;
        }

        if (!counted) {
            // An uncounted line is not a zero. Showing "Match" here would let a
            // sheet be submitted with rows nobody actually looked at.
            slot.innerHTML = '';
            const pending = document.createElement('span');
            pending.className = 'inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300';
            pending.textContent = t('Not counted');
            slot.append(pending);
            return;
        }

        slot.innerHTML = '';
        const chip = badgeFor(Number(raw) - system);
        if (chip) {
            slot.append(chip);
        }
    };

    /**
     * Fold every counted line into the four summary tiles.
     */
    const recalcSummary = () => {
        const rows = Array.from(tbody.querySelectorAll('[data-opname-row]'));
        let items = 0;
        let matched = 0;
        let deficit = 0;
        let surplus = 0;

        rows.forEach((row) => {
            if (row.dataset.counted !== '1') {
                return;
            }

            const variance = Number(row.dataset.variance || 0);
            items += 1;

            if (variance === 0) {
                matched += 1;
            } else if (variance < 0) {
                deficit += Math.abs(variance);
            } else {
                surplus += variance;
            }
        });

        const set = (key, value) => {
            const tile = root.querySelector(`[data-summary-metric="${key}"] [data-summary-value]`);
            if (tile) {
                tile.textContent = format(value);
            }
        };

        set('items', items);
        set('matched', matched);
        set('deficit', deficit);
        set('surplus', surplus);

        const net = surplus - deficit;
        const netTile = root.querySelector('[data-summary-metric="net"] [data-summary-value]');
        if (netTile) {
            netTile.textContent = `${net > 0 ? '+' : net < 0 ? '-' : ''}${format(net)}`;
            netTile.classList.toggle('text-green-700', net === 0);
            netTile.classList.toggle('dark:text-green-400', net === 0);
            netTile.classList.toggle('text-red-700', net < 0);
            netTile.classList.toggle('dark:text-red-400', net < 0);
            netTile.classList.toggle('text-blue-700', net > 0);
            netTile.classList.toggle('dark:text-blue-400', net > 0);
            netTile.classList.toggle('text-gray-900', net === 0);
            netTile.classList.toggle('dark:text-gray-100', net === 0);
        }

        return { items, matched, deficit, surplus, net };
    };

    /**
     * Input names carry a positional index. Cloning a row copies the template's
     * index, so every clone is renumbered before the next recalculation.
     */
    const syncNames = () => {
        Array.from(tbody.querySelectorAll('[data-opname-row]')).forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/\[\d+\]/, `[${index}]`);
            });
            row.dataset.rowIndex = String(index);
        });
    };

    const refreshEmptyState = () => {
        const empty = tbody.querySelectorAll('[data-opname-row]').length === 0;
        // An empty tbody renders nothing anyway, but the "nothing on the sheet"
        // prompt and the table header should not both be on screen.
        if (emptyState) {
            emptyState.classList.toggle('hidden', !empty);
        }
        tbody.classList.toggle('hidden', empty);
    };

    const addRow = (product) => {
        if (added().has(String(product.id))) {
            return false;
        }

        const row = rowTemplate.content.firstElementChild.cloneNode(true);
        row.dataset.productId = String(product.id);
        row.dataset.systemStock = String(product.current_stock ?? 0);
        row.querySelector('[data-row-product-name]').textContent = product.name;
        row.querySelector('[data-row-product-sku]').textContent = product.sku;
        row.querySelector('[data-row-system-stock]').textContent = format(product.current_stock ?? 0);
        row.querySelector('[name$="[product_id]"]').value = String(product.id);

        tbody.append(row);
        refreshEmptyState();
        syncNames();
        recalcRow(row);
        recalcSummary();
        renderResults();
        physicalInput(row)?.focus();

        return true;
    };

    /* ---- product picker ---------------------------------------------- */

    const matches = (product) => {
        const term = (search?.value ?? '').trim().toLowerCase();
        const category = categoryFilter?.value ?? '';

        if (category && String(product.category_id ?? '') !== category) {
            return false;
        }

        if (!term) {
            return true;
        }

        return `${product.name} ${product.sku}`.toLowerCase().includes(term);
    };

    function renderResults() {
        if (!results) {
            return;
        }

        const already = added();
        const hits = catalogue.filter(matches);
        const limit = 50;
        results.innerHTML = '';

        hits.slice(0, limit).forEach((product) => {
            const isAdded = already.has(String(product.id));
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 border-b border-gray-100 px-3 py-2 last:border-b-0 dark:border-gray-700';

            const text = document.createElement('div');
            text.className = 'min-w-0';

            const name = document.createElement('p');
            name.className = 'truncate text-sm font-medium text-gray-900 dark:text-white';
            name.textContent = product.name;

            const meta = document.createElement('p');
            meta.className = 'truncate font-mono text-xs text-gray-500 dark:text-gray-400';
            meta.textContent = `${product.sku} - ${t('system stock')} ${format(product.current_stock ?? 0)}`;

            text.append(name, meta);

            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = t(isAdded ? 'On sheet' : 'Add');
            button.disabled = isAdded;
            button.className = isAdded
                ? 'shrink-0 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                : 'shrink-0 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400';

            button.addEventListener('click', () => addRow(product));

            row.append(text, button);
            results.append(row);
        });

        if (hits.length === 0) {
            const none = document.createElement('p');
            none.className = 'px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400';
            none.textContent = t('No products match that filter.');
            results.append(none);
        }

        if (resultsCount) {
            resultsCount.textContent = hits.length > limit
                ? t('Showing the first :limit of :total matches.', { limit, total: format(hits.length) })
                : t(hits.length === 1 ? ':count product available.' : ':count products available.', { count: format(hits.length) });
        }
    }

    /* ---- delegated events -------------------------------------------- */

    tbody.addEventListener('input', (event) => {
        const row = event.target.closest('[data-opname-row]');

        if (row && event.target.matches('[data-physical-input]')) {
            recalcRow(row);
            recalcSummary();
        }
    });

    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-row]');

        if (!button) {
            return;
        }

        button.closest('[data-opname-row]')?.remove();
        refreshEmptyState();
        syncNames();
        recalcSummary();
        renderResults();
    });

    search?.addEventListener('input', renderResults);
    categoryFilter?.addEventListener('change', renderResults);

    /* ---- confirmation ------------------------------------------------ */

    const uncounted = () => tbody.querySelectorAll('[data-opname-row][data-counted="0"]');

    const showAlert = (message) => {
        if (!alert) {
            return;
        }

        alert.textContent = message;
        alert.classList.remove('hidden');
    };

    const clearAlert = () => alert?.classList.add('hidden');

    const rowSummary = () => {
        let items = 0;
        let matched = 0;
        let deficit = 0;
        let surplus = 0;

        tbody.querySelectorAll('[data-opname-row][data-counted="1"]').forEach((row) => {
            const variance = Number(row.dataset.variance || 0);
            items += 1;

            if (variance === 0) {
                matched += 1;
            } else if (variance < 0) {
                deficit += Math.abs(variance);
            } else {
                surplus += variance;
            }
        });

        return { items, matched, deficit, surplus, net: surplus - deficit };
    };

    /**
     * Fill the confirm dialog. Only lines that actually disagree are listed:
     * "12 lines matched" in a dialog listing 12 even lines is the one screen in
     * this flow nobody needs to read twice.
     */
    const buildConfirmation = () => {
        const discrepant = Array.from(tbody.querySelectorAll('[data-opname-row]'))
            .filter((row) => row.dataset.counted === '1' && Number(row.dataset.variance || 0) !== 0);

        if (confirmBody) {
            confirmBody.innerHTML = '';
            confirmTotals?.classList.toggle('hidden', discrepant.length === 0);

            discrepant.forEach((row) => {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-gray-100 last:border-b-0 dark:border-gray-700';

                const name = row.querySelector('[data-row-product-name]')?.textContent ?? '';
                const sku = row.querySelector('[data-row-product-sku]')?.textContent ?? '';
                const system = row.dataset.systemStock ?? '0';
                const physical = physicalInput(row)?.value ?? '0';
                const variance = Number(row.dataset.variance || 0);
                const reason = row.querySelector('[name$="[reason]"]')?.value?.trim();

                const cells = [
                    { text: name, sub: sku, align: 'left' },
                    { text: format(system) },
                    { text: format(physical) },
                    { text: signed(variance) },
                    { text: reason || t('No reason given'), align: 'left', muted: true },
                ];

                cells.forEach((cell) => {
                    const td = document.createElement('td');
                    td.className = `px-3 py-2 text-sm ${cell.align === 'left' ? 'text-left' : 'text-right tabular-nums'} ${cell.muted ? 'text-gray-500 dark:text-gray-400' : 'text-gray-900 dark:text-white'}`;

                    if (cell.sub) {
                        const strong = document.createElement('span');
                        strong.className = 'block font-medium';
                        strong.textContent = cell.text;

                        const small = document.createElement('span');
                        small.className = 'block font-mono text-xs text-gray-500 dark:text-gray-400';
                        small.textContent = cell.sub;

                        td.append(strong, small);
                    } else {
                        td.textContent = cell.text;
                    }

                    tr.append(td);
                });

                confirmBody.append(tr);
            });
        }

        const totals = rowSummary();

        if (confirmTotals) {
            const set = (key, value) => {
                const el = confirmTotals.querySelector(`[data-confirm-total="${key}"]`);
                if (el) {
                    el.textContent = format(value);
                }
            };

            set('items', totals.items);
            set('matched', totals.matched);
            set('deficit', totals.deficit);
            set('surplus', totals.surplus);
        }
    };

    /* ---- submit ------------------------------------------------------ */

    form.addEventListener('submit', (event) => {
        // The submit button opens the dialog instead of posting. The dialog's
        // own button sets this flag and posts for real.
        if (form.dataset.opnameConfirmed === '1') {
            return;
        }

        event.preventDefault();
        clearAlert();

        if (tbody.querySelectorAll('[data-opname-row]').length === 0) {
            showAlert(t('Add at least one product before submitting the count.'));
            return;
        }

        const pending = uncounted();

        if (pending.length > 0) {
            showAlert(t(pending.length === 1
                ? ':count line still needs a physical count. Fill every line in, or remove the ones you did not count.'
                : ':count lines still need a physical count. Fill every line in, or remove the ones you did not count.', { count: pending.length }));
            pending[0].querySelector('[data-physical-input]')?.focus();
            return;
        }

        const negatives = Array.from(tbody.querySelectorAll('[data-physical-input]'))
            .filter((field) => Number(field.value) < 0);

        if (negatives.length > 0) {
            showAlert(t('A physical count cannot be negative.'));
            negatives[0].focus();
            return;
        }

        buildConfirmation();
        openConfirm();
    });

    document.querySelectorAll('[data-opname-confirm-submit]').forEach((button) => {
        button.addEventListener('click', () => {
            form.dataset.opnameConfirmed = '1';
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });
    });

    const closeConfirm = () => {
        if (!confirmModal) {
            return;
        }

        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
        confirmModal.setAttribute('aria-hidden', 'true');
    };

    const openConfirm = () => {
        if (!confirmModal) {
            return;
        }

        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('flex');
        confirmModal.setAttribute('aria-hidden', 'false');
        // The shared shell puts tabindex="-1" on its root, so focusing the
        // container moves the screen reader here without needing a title id.
        confirmModal.focus?.();
    };

    document.querySelectorAll('[data-opname-confirm-cancel]').forEach((button) => {
        button.addEventListener('click', closeConfirm);
    });

    // The shared modal shell renders a data-modal-hide close button. Flowbite
    // only wires that for modals it instantiated via data-modal-toggle, and this
    // one is opened by hand, so without this the header X would be dead.
    confirmModal?.addEventListener('click', (event) => {
        if (event.target.closest('[data-modal-hide]')) {
            closeConfirm();
        }

        // Clicking the backdrop closes too, which is the Flowbite convention.
        if (event.target === confirmModal) {
            closeConfirm();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && confirmModal && !confirmModal.classList.contains('hidden')) {
            closeConfirm();
        }
    });

    /* ---- boot -------------------------------------------------------- */

    /**
     * Repopulate the sheet after a rejected submission.
     *
     * Rows come back through addRow() so a redisplayed form is built from the
     * same template a live row uses; there is no second copy of the markup that
     * could drift from it.
     */
    const restore = () => {
        let pending = [];
        try {
            pending = JSON.parse(root.dataset.opnameRestore || '[]');
        } catch (e) {
            pending = [];
        }

        pending.forEach((line) => {
            const product = catalogue.find((p) => String(p.id) === String(line.product_id));
            if (!product || !addRow(product)) {
                return;
            }

            const rows = tbody.querySelectorAll('[data-opname-row]');
            const row = rows[rows.length - 1];

            const field = row.querySelector('[data-physical-input]');
            if (field) {
                field.value = line.physical_stock ?? '';
            }

            const reason = row.querySelector('[name$="[reason]"]');
            if (reason) {
                reason.value = line.reason ?? '';
            }

            recalcRow(row);
        });
    };

    refreshEmptyState();
    syncNames();
    restore();
    recalcSummary();
    renderResults();
}

document.addEventListener('DOMContentLoaded', initOpnameSheet);