/*
 * Toasts: short confirmations that go away on their own and never move the page.
 * A success message from the server arrives as <template data-flash="success"> (see the layout); scripts call
 * window.adminToast({ text, tone }). Errors stay in the page: they have to be read and acted on.
 */
const region = document.getElementById('toasts');
const template = document.getElementById('toast-template');

const SHOW_MS = 5000;
const active = new Set();

function dismiss(node) {
    active.delete(node);
    node.classList.add('is-leaving');
    setTimeout(() => node.remove(), 200);
}

/** tone: success | info | error. Errors stay until dismissed. */
function show({ text, tone = 'success' }) {
    if (!region || !template || !text) return null;

    const node = template.content.firstElementChild.cloneNode(true);
    node.dataset.tone = tone;
    node.querySelector('[data-toast-text]').textContent = text;
    node.querySelector('.toast-close').addEventListener('click', () => dismiss(node));

    // Paused while the pointer or keyboard focus is on it, and while the tab is in the background.
    let remaining = SHOW_MS;
    let startedAt = 0;
    let timer = null;
    let holds = 0;

    const run = () => {
        if (tone === 'error' || holds > 0 || document.hidden || timer) return;
        startedAt = Date.now();
        timer = setTimeout(() => dismiss(node), remaining);
    };
    const hold = () => {
        holds += 1;
        if (timer) {
            clearTimeout(timer);
            timer = null;
            remaining = Math.max(1000, remaining - (Date.now() - startedAt));
        }
    };
    const release = () => {
        holds = Math.max(0, holds - 1);
        run();
    };

    node.addEventListener('pointerenter', hold);
    node.addEventListener('pointerleave', release);
    node.addEventListener('focusin', hold);
    node.addEventListener('focusout', release);
    node.pause = hold;
    node.resume = release;

    active.add(node);
    region.append(node);
    run();
    return node;
}

// A tab in the background shouldn't use up the time.
document.addEventListener('visibilitychange', () => {
    active.forEach((node) => (document.hidden ? node.pause() : node.resume()));
});

window.adminToast = show;

// Messages from the server. A short pause makes sure the (already present, empty) live region announces them.
document.querySelectorAll('template[data-flash]').forEach((flash) => {
    const text = flash.content.textContent.trim();
    setTimeout(() => show({ text, tone: flash.dataset.flash || 'success' }), 250);
});
