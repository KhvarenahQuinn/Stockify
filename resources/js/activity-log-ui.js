/**
 * Activity log detail modal.
 *
 * The report renders one modal for every entry rather than one per row: a
 * report can list a thousand entries, and a modal per row would triple the
 * page weight for a control most visitors never open. Each trigger carries its
 * row's values in data attributes and this module copies them in.
 *
 * Values are assigned with textContent, never innerHTML, so a description or a
 * changed value cannot inject markup into the audit view.
 */
import { t } from './i18n';

const MODAL_ID = 'activity-log-detail-modal';
const DASH = '\u2014';

function part(root, name) {
    return root.querySelector(`[data-activity-detail-${name}]`);
}

function setText(root, name, value) {
    const target = part(root, name);

    if (target) {
        target.textContent = value === null || value === undefined || value === '' ? DASH : value;
    }
}

function parseJson(value, fallback) {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        return fallback;
    }
}

function renderChanges(root, changes) {
    const section = part(root, 'changes');
    const title = part(root, 'changes-title');
    const body = root.querySelector('[data-activity-detail-change-rows]');
    const empty = part(root, 'empty');

    if (!section || !body) {
        return;
    }

    const rows = Array.isArray(changes) ? changes : [];

    // A deleted record has no "after" side, and a newly added value has no
    // "before" side. Naming the table after what happened, and dropping the
    // column that is nothing but dashes, reads better than a generic "What
    // changed" and keeps the table inside the card on a phone.
    const isBlank = (value) => value === DASH || value === null || value === undefined || value === '';
    const removed = rows.length > 0 && rows.every((row) => isBlank(row.new));
    const added = rows.length > 0 && rows.every((row) => isBlank(row.old));

    body.replaceChildren();

    rows.forEach((change) => {
        const row = document.createElement('tr');

        [change.label, change.old, change.new].forEach((value, index) => {
            const cell = document.createElement(index === 0 ? 'th' : 'td');

            if (index === 0) {
                cell.scope = 'row';
                cell.className = 'px-3 py-2 font-medium text-gray-900 dark:text-white';
            } else {
                cell.className = 'px-3 py-2 text-gray-600 dark:text-gray-300';
                cell.hidden = (index === 1 && added) || (index === 2 && removed);
            }

            cell.textContent = isBlank(value) ? DASH : String(value);
            row.append(cell);
        });

        body.append(row);
    });

    [['before', added], ['after', removed]].forEach(([column, hide]) => {
        const header = part(root, `th-${column}`);

        if (header) {
            header.hidden = hide;
        }
    });

    if (title) {
        title.textContent = removed ? t('Removed values') : (added ? t('Added values') : t('What changed'));
    }

    section.hidden = rows.length === 0;

    if (empty) {
        empty.hidden = rows.length > 0;
    }
}

function renderPayload(root, properties) {
    const details = part(root, 'payload');
    const pre = part(root, 'json');

    if (!details || !pre) {
        return;
    }

    // `subject` and `note` are already rendered above, so only a payload with
    // something left to say is worth dumping; otherwise the reader sees the
    // same values twice.
    const remaining = properties !== null && typeof properties === 'object'
        ? Object.fromEntries(
            Object.entries(properties).filter(([key]) => key !== 'subject' && key !== 'note'),
        )
        : {};

    const hasPayload = Object.keys(remaining).length > 0;

    details.hidden = !hasPayload;
    details.open = false;
    pre.textContent = hasPayload ? JSON.stringify(remaining, null, 2) : '';
}

function populate(root, trigger) {
    const data = trigger.dataset;
    const changes = parseJson(data.changes, []);
    const properties = parseJson(data.properties, {});

    setText(root, 'action', data.action);
    setText(root, 'description', data.description);
    setText(root, 'user', data.user);
    setText(root, 'role', data.role);
    setText(root, 'time', data.time);
    setText(root, 'ip', data.ip);

    const subjectWrap = part(root, 'subject-wrap');
    const subject = part(root, 'subject');

    if (subjectWrap && subject) {
        subject.textContent = data.subject || '';
        subjectWrap.hidden = !data.subject;
    }

    const note = part(root, 'note');
    const noteText = part(root, 'note-text');

    if (note && noteText) {
        noteText.textContent = data.note || '';
        note.hidden = !data.note;
    }

    renderChanges(root, changes);
    renderPayload(root, changes.length === 0 ? properties : {});
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById(MODAL_ID)) {
        return;
    }

    // Nothing else on this page initialises Flowbite, so the data-modal
    // attributes on the trigger and the close buttons would otherwise be inert.
    if (typeof window.initFlowbite === 'function') {
        window.initFlowbite();
    }

    // Capture phase: fill the modal before Flowbite's own click handler runs,
    // so it never opens showing the previous row's values.
    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest('[data-activity-detail]')
            : null;

        if (!trigger) {
            return;
        }

        const modal = document.getElementById(MODAL_ID);

        if (modal) {
            populate(modal, trigger);
        }
    }, true);
});
