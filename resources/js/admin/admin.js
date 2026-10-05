import './live-map.js';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

/* Mobile navigation drawer */
(() => {
    const side = $('[data-side]');
    const scrim = $('[data-nav-scrim]');
    const toggle = $('[data-nav-toggle]');
    if (!side || !scrim || !toggle) return;

    const set = (open) => {
        side.classList.toggle('is-open', open);
        scrim.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('nav-open', open);
    };

    toggle.addEventListener('click', () => set(!side.classList.contains('is-open')));
    scrim.addEventListener('click', () => set(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') set(false); });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => { if (e.matches) set(false); });
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

/* Confirm dialog for any form with data-confirm-title */
const dialog = $('#confirmDialog');
let pending = null;

function openConfirm(form, submitter) {
    pending = { form, submitter };
    const tone = form.dataset.confirmTone || 'danger';
    dialog.dataset.tone = tone;
    $('[data-cf-title]', dialog).textContent = form.dataset.confirmTitle;
    $('[data-cf-text]', dialog).textContent = form.dataset.confirmText || '';
    const ok = $('[data-cf-ok]', dialog);
    ok.textContent = form.dataset.confirmOk || 'Confirm';
    ok.className = 'btn ' + (tone === 'ok' ? 'btn-ok' : tone === 'primary' ? 'btn-primary' : 'btn-danger');
    dialog.showModal();
}

if (dialog) {
    dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close('cancel'); });
    dialog.addEventListener('close', () => {
        if (dialog.returnValue === 'confirm' && pending) {
            const { form, submitter } = pending;
            form.dataset.confirmed = '1';
            form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
        }
        pending = null;
        dialog.returnValue = '';
    });
}

/* One submit handler: confirm first, then show a loading state on the button */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (dialog && form.dataset.confirmTitle && form.dataset.confirmed !== '1') {
        e.preventDefault();
        openConfirm(form, e.submitter);
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

/* Back/forward cache: reset any loading buttons */
window.addEventListener('pageshow', () => {
    $$('.is-loading').forEach((button) => {
        button.classList.remove('is-loading');
        button.disabled = false;
        button.removeAttribute('aria-busy');
    });
    $$('form[data-confirmed]').forEach((form) => delete form.dataset.confirmed);
});