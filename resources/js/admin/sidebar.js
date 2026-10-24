/*
 * Sidebar behaviour that belongs to the page chrome rather than to one feature:
 *   - collapse the sidebar to icons (desktop) and open/close its groups
 *   - tooltips for the collapsed sidebar
 *   - the emergency-alert status row and its alarm-settings popover
 *
 * The server already renders the remembered state from two plain cookies (see AppServiceProvider), so nothing
 * flashes on load. This file only keeps those cookies up to date.
 */
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

const side = $('[data-side]');

if (side) {
    const RAIL_COOKIE = 'gobike_admin_rail';
    const CLOSED_COOKIE = 'gobike_admin_closed';
    const desktop = window.matchMedia('(min-width: 1024px)');

    /* ---------- Cookies ---------- */

    const readCookie = (name) => {
        const match = document.cookie.match(new RegExp(`(?:^|;\s*)${name}=([^;]*)`));
        return match ? decodeURIComponent(match[1]) : '';
    };

    // Same style as the cookie-consent cookie: plain, short, scoped to the admin.
    const writeCookie = (name, value) => {
        const secure = location.protocol === 'https:' ? '; Secure' : '';
        const age = value ? 15552000 : 0;
        document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${age}; Path=/admin; SameSite=Lax${secure}`;
    };

    const isRail = () => document.body.classList.contains('side-rail');

    /* ---------- Collapse to icons ---------- */

    const toggle = $('[data-side-toggle]', side);
    let animTimer = null;

    function setRail(on) {
        document.body.classList.toggle('side-rail', on);
        const label = on ? 'Expand sidebar' : 'Collapse sidebar';
        toggle?.setAttribute('aria-label', label);
        if (toggle) toggle.dataset.tip = label;
        writeCookie(RAIL_COOKIE, on ? '1' : '');

        // Labels fade back in when the sidebar opens.
        document.body.classList.add('side-anim');
        clearTimeout(animTimer);
        animTimer = setTimeout(() => document.body.classList.remove('side-anim'), 260);

        hideTip();

        // The content area just changed width: charts and the map measure themselves again right away.
        requestAnimationFrame(() => {
            window.dispatchEvent(new Event('resize'));
            document.dispatchEvent(new CustomEvent('gobike:layout'));
        });
    }

    toggle?.addEventListener('click', () => setRail(!isRail()));

    /* ---------- Groups ---------- */

    // The closed set lives in the cookie, not in the DOM: the group holding the current page is forced open, and
    // reading the DOM would silently forget that it was closed.
    let closed = readCookie(CLOSED_COOKIE).split('.').filter(Boolean);

    $$('[data-group-toggle]', side).forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.groupToggle;
            const open = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(open));
            closed = closed.filter((item) => item !== key);
            if (!open) closed.push(key);
            writeCookie(CLOSED_COOKIE, closed.join('.'));
        });
    });

    // Back/forward cache: the page comes back as it was left, so re-read what the cookies say now.
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        closed = readCookie(CLOSED_COOKIE).split('.').filter(Boolean);
        document.body.classList.toggle('side-rail', readCookie(RAIL_COOKIE) === '1');
        $$('[data-group-toggle]', side).forEach((button) => {
            const key = button.dataset.groupToggle;
            const here = $('[aria-current="page"]', button.closest('[data-group]'));
            button.setAttribute('aria-expanded', String(Boolean(here) || !closed.includes(key)));
        });
    });

    /* ---------- Tooltips (collapsed sidebar only) ---------- */

    const tip = document.createElement('div');
    tip.className = 'side-tip';
    tip.setAttribute('aria-hidden', 'true'); // the link text already names the link
    document.body.append(tip);

    let tipTimer = null;

    function showTip(element) {
        if (!isRail() || !desktop.matches) return;
        const text = element.dataset.tip;
        if (!text) return;
        tip.textContent = text;
        const box = element.getBoundingClientRect();
        const left = Math.round(box.right + 10);
        const top = Math.round(box.top + box.height / 2 - tip.offsetHeight / 2);
        tip.style.transform = `translate(${left}px, ${top}px)`;
        tip.classList.add('is-visible');
    }

    function hideTip() {
        clearTimeout(tipTimer);
        tip.classList.remove('is-visible');
    }

    side.addEventListener('pointerover', (e) => {
        if (e.pointerType === 'touch') return;
        const element = e.target.closest('[data-tip]');
        if (!element || !side.contains(element)) return;
        clearTimeout(tipTimer);
        tipTimer = setTimeout(() => showTip(element), 300);
    });
    side.addEventListener('pointerout', hideTip);
    side.addEventListener('focusin', (e) => {
        const element = e.target.closest('[data-tip]');
        if (element) showTip(element);
    });
    side.addEventListener('focusout', hideTip);
    side.addEventListener('click', hideTip);
    $('.side-nav', side)?.addEventListener('scroll', hideTip, { passive: true });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideTip(); });

    /* ---------- Emergency-alert status ---------- */

    const status = $('[data-side-status]', side);
    const alertRoot = document.getElementById('emergency-alert');

    if (status && alertRoot) {
        const els = {
            text: $('[data-ss-text]', status),
            meta: $('[data-ss-meta]', status),
            detail: $('[data-ss-detail]', status),
            button: $('.side-status-btn', status),
            pop: $('#side-alarm', status),
        };
        // emergency-alert.js runs first and keeps its latest state on the element, so the first event is never missed.
        let current = { state: alertRoot.dataset.state || 'connecting', lastOk: Number(alertRoot.dataset.lastOk) || null, soundOn: true, blocked: false };

        const ago = (time) => {
            if (!time) return null;
            const seconds = Math.max(0, Math.round((Date.now() - time) / 1000));
            if (seconds < 10) return 'just now';
            if (seconds < 60) return `${seconds} s ago`;
            const minutes = Math.floor(seconds / 60);
            return minutes < 60 ? `${minutes} min ago` : `${Math.floor(minutes / 60)} h ago`;
        };

        function render() {
            const { state, lastOk, soundOn, blocked } = current;
            const checked = ago(lastOk);
            const sound = blocked ? 'Sound blocked' : soundOn ? 'Sound on' : 'Sound off';

            const copy = {
                connecting: ['Connecting…', 'Emergency alerts', 'Checking for emergencies…'],
                live: ['Alerts live', `${sound}${checked ? ` · checked ${checked}` : ''}`,
                    `Checked ${checked ?? 'just now'}. This page asks the server every few seconds.`],
                reconnecting: ['Reconnecting…', checked ? `Last checked ${checked}` : 'Trying again', 'The last check failed. Trying again.'],
                offline: ['Alerts offline', checked ? `Last checked ${checked}` : 'Cannot reach the server',
                    'Cannot reach the server, so emergency alerts may be late. Retrying…'],
                'signed-out': ['Signed out', 'Alerts are off', 'You are signed out, so emergency alerts are off. Sign in again to get them.'],
            }[state] ?? ['Emergency alerts', '', ''];

            status.dataset.state = state;
            els.text.textContent = copy[0];
            els.meta.textContent = copy[1];
            els.detail.textContent = copy[2];
        }

        document.addEventListener('gobike:alert-status', (e) => {
            current = { ...current, ...e.detail };
            render();
        });
        setInterval(render, 15000);

        status.hidden = false;
        render();

        // Popover placement: above the row when the sidebar is open, beside it when collapsed.
        els.pop?.addEventListener('beforetoggle', (e) => {
            if (e.newState !== 'open') return;
            const box = els.button.getBoundingClientRect();
            const beside = isRail() && desktop.matches;
            els.pop.style.left = `${Math.round(beside ? box.right + 10 : box.left)}px`;
            els.pop.style.bottom = `${Math.round(window.innerHeight - (beside ? box.bottom : box.top - 8))}px`;
        });
    }
}
