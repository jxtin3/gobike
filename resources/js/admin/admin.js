import './live-map.js';
import './reports.js';
import './emergency-alert.js';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const csrf = () => $('meta[name="csrf-token"]')?.content ?? '';

/* Mobile navigation drawer */
(() => {
    const side = $('[data-side]');
    const scrim = $('[data-nav-scrim]');
    const toggle = $('[data-nav-toggle]');
    if (!side || !scrim || !toggle) return;

    const desktop = window.matchMedia('(min-width: 1024px)');

    const set = (open, { restoreFocus = false } = {}) => {
        side.classList.toggle('is-open', open);
        scrim.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('nav-open', open);
        // Off-screen links must not be reachable with Tab on small screens.
        side.inert = !open && !desktop.matches;

        if (open) $('a, button', side)?.focus();
        else if (restoreFocus) toggle.focus();
    };

    toggle.addEventListener('click', () => set(!side.classList.contains('is-open')));
    scrim.addEventListener('click', () => set(false, { restoreFocus: true }));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && side.classList.contains('is-open')) set(false, { restoreFocus: true });
    });
    desktop.addEventListener('change', () => set(false));
    set(false);
})();

/* Flash messages: close button + auto-dismiss */
$$('.alert[data-dismissible]').forEach((el) => {
    const hide = () => {
        el.classList.add('is-leaving');
        setTimeout(() => el.remove(), 200);
    };
    $('.alert-close', el)?.addEventListener('click', hide);
    if (el.dataset.autodismiss) setTimeout(hide, Number(el.dataset.autodismiss));
});

/* Shared confirm dialog */
const dialog = $('#confirmDialog');
let onConfirmClose = null;

function showConfirm({ title, text = '', ok = 'Confirm', tone = 'danger' }, done) {
    dialog.dataset.tone = tone;
    $('[data-cf-title]', dialog).textContent = title;
    $('[data-cf-text]', dialog).textContent = text;
    const okButton = $('[data-cf-ok]', dialog);
    okButton.textContent = ok;
    okButton.className = 'btn ' + (tone === 'ok' ? 'btn-ok' : tone === 'primary' ? 'btn-primary' : 'btn-danger');
    onConfirmClose = done;
    dialog.showModal();
}

if (dialog) {
    dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close('cancel'); });
    dialog.addEventListener('close', () => {
        const done = onConfirmClose;
        const confirmed = dialog.returnValue === 'confirm';
        onConfirmClose = null;
        dialog.returnValue = '';
        done?.(confirmed);
    });
}

/** Ask with the shared dialog from page scripts: `if (await adminConfirm({ title, text, ok })) …` */
window.adminConfirm = (options) => new Promise((resolve) => {
    if (!dialog) {
        resolve(window.confirm([options.title, options.text].filter(Boolean).join('\n')));
        return;
    }
    showConfirm(options, resolve);
});

/* One submit handler: confirm first (data-confirm-title on the form or the button), then show a loading state */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || e.defaultPrevented) return;

    // A submit button can carry its own data-confirm-* (e.g. only the "Cancelled" status asks first).
    const submitter = e.submitter;
    const ask = submitter?.dataset.confirmTitle ? submitter.dataset : form.dataset;

    if (dialog && ask.confirmTitle && form.dataset.confirmed !== '1') {
        e.preventDefault();
        showConfirm({
            title: ask.confirmTitle,
            text: ask.confirmText,
            ok: ask.confirmOk,
            tone: ask.confirmTone,
        }, (confirmed) => {
            if (confirmed) {
                form.dataset.confirmed = '1';
                form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
            } else {
                form.dispatchEvent(new CustomEvent('confirm:cancel'));
            }
        });
        return;
    }

    if (form.method !== 'post') return;
    const button = e.submitter || $('button[type="submit"], button:not([type])', form);
    if (!button) return;

    // Disable after the browser has read the form, so nothing is dropped.
    setTimeout(() => {
        button.classList.add('is-loading');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }, 0);
});

/*
 * Selects that save as soon as they change. An <option data-confirm-title="…"> asks first;
 * cancelling puts the select back to its previous value.
 */
$$('select[data-autosubmit]').forEach((select) => {
    let saved = select.value;
    const form = select.form;
    if (!form) return;

    select.addEventListener('change', () => {
        const option = select.selectedOptions[0];
        ['confirmTitle', 'confirmText', 'confirmOk', 'confirmTone'].forEach((key) => {
            if (option?.dataset[key]) form.dataset[key] = option.dataset[key];
            else delete form.dataset[key];
        });
        form.requestSubmit();
    });

    form.addEventListener('confirm:cancel', () => { select.value = saved; });
    window.addEventListener('pageshow', () => { saved = select.value; });
});

/* Unread counters in the sidebar and on the page */
function decrementCounter(element) {
    if (!element) return;
    const next = Math.max(0, Number(element.textContent.replace(/[^\d]/g, '')) - 1);
    if (element.matches('.badge') && next === 0) {
        element.remove();
        return;
    }
    const label = element.querySelector('.count-num');
    (label || element).textContent = new Intl.NumberFormat().format(next);
}

/*
 * Detail dialogs. A table row with data-dialog="#id" opens that dialog when the row or its
 * [data-open] button is clicked, and fills it from the row's data-* attributes:
 *   [data-field=key]          text (— when empty)
 *   [data-pill-field=key]     pill text, colour from data-{key}-pill
 *   [data-href-field=key]     link href
 *   [data-action-field=key]   form action
 *   [data-show-field=key]     shown only when the value is set and not "0"
 *   [data-confirm-field=key]  form's confirm text
 *   [data-pressed-field=key]  buttons inside get aria-pressed when their value matches
 * Rows with data-read-url and data-is-read="0" are marked read when opened.
 */
(() => {
    const isSet = (value) => value !== undefined && value !== '' && value !== '0';

    function fill(dlg, data) {
        $$('[data-field]', dlg).forEach((el) => { el.textContent = data[el.dataset.field] || '—'; });
        $$('[data-pill-field]', dlg).forEach((el) => {
            const key = el.dataset.pillField;
            el.textContent = data[key] || '—';
            el.className = `pill pill-${data[`${key}Pill`] || 'neutral'}`;
        });
        $$('[data-href-field]', dlg).forEach((el) => { el.href = data[el.dataset.hrefField] || '#'; });
        $$('[data-action-field]', dlg).forEach((el) => { el.action = data[el.dataset.actionField] || ''; });
        $$('[data-show-field]', dlg).forEach((el) => { el.hidden = !isSet(data[el.dataset.showField]); });
        $$('[data-confirm-field]', dlg).forEach((el) => { el.dataset.confirmText = data[el.dataset.confirmField] || ''; });
        $$('[data-pressed-field]', dlg).forEach((group) => {
            const current = data[group.dataset.pressedField];
            $$('button[value]', group).forEach((button) => {
                button.setAttribute('aria-pressed', String(button.value === current));
            });
        });
    }

    async function markRead(row, dlg) {
        if (!row.dataset.readUrl || row.dataset.isRead !== '0') return;
        try {
            const response = await fetch(row.dataset.readUrl, {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            if (!response.ok) throw new Error(String(response.status));
        } catch {
            const error = $('[data-dialog-error]', dlg);
            if (error) {
                error.textContent = 'The message is open, but it could not be marked as read. Please try again.';
                error.hidden = false;
            }
            return;
        }

        row.dataset.isRead = '1';
        row.dataset.status = 'Read';
        row.dataset.statusPill = 'neutral';
        row.classList.remove('is-unread');
        const rowPill = $('[data-read-pill]', row);
        if (rowPill) {
            rowPill.textContent = 'Read';
            rowPill.className = 'pill pill-neutral';
        }
        $$('[data-pill-field="status"]', dlg).forEach((el) => {
            el.textContent = 'Read';
            el.className = 'pill pill-neutral';
        });
        decrementCounter($(`[data-nav-badge="${row.dataset.badge}"]`));
        $$('[data-unread-count]').forEach(decrementCounter);
    }

    function open(row) {
        const dlg = $(row.dataset.dialog);
        if (!dlg) return;
        const error = $('[data-dialog-error]', dlg);
        if (error) error.hidden = true;
        fill(dlg, row.dataset);
        dlg.showModal();
        markRead(row, dlg);
    }

    document.addEventListener('click', (e) => {
        const row = e.target.closest('tr[data-dialog]');
        if (!row) return;
        const control = e.target.closest('a, button, input, select, textarea, label, form');
        if (control && !control.matches('[data-open]')) return;
        open(row);
    });

    $$('dialog.msg-modal').forEach((dlg) => {
        dlg.addEventListener('click', (e) => {
            if (e.target === dlg || e.target.closest('[data-dialog-close]')) dlg.close();
        });
    });
})();

/* Print buttons (reports and patient records are styled for paper / Save as PDF) */
$$('[data-print]').forEach((button) => button.addEventListener('click', () => window.print()));

/* Back/forward cache: reset any loading buttons */
window.addEventListener('pageshow', () => {
    $$('.is-loading').forEach((button) => {
        button.classList.remove('is-loading');
        button.disabled = false;
        button.removeAttribute('aria-busy');
    });
    $$('form[data-confirmed]').forEach((form) => delete form.dataset.confirmed);
});
