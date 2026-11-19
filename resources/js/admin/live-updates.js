/*
 * Live updates on every admin page (root: [data-live-updates] in admin/operations/layout.blade.php; data from
 * Admin\LiveUpdatesController / App\Support\AdminLiveFeed).
 *
 * While the tab is visible it asks every few seconds what came in since the last look: new accounts, volunteer
 * applications, messages, home-visit requests, training requests, chapter inquiries, donations and feedback.
 *  - The sidebar counts follow along (the badges are always in the page, hidden at zero).
 *  - Each new thing gets a toast with a link to open it. Several of a kind are grouped into one toast.
 *  - On the list page of that kind, a "N new — show" pill appears instead, so the table never changes under the admin.
 * The first answer only sets the starting point: opening a page never toasts what was already there.
 * Emergencies and SOS requests are not handled here: emergency-alert.js rings for those.
 */
(() => {
    const root = document.querySelector('[data-live-updates]');
    if (!root || !root.dataset.url || !window.fetch) return;

    const POLL_MS = 10000;
    const SLOW_POLL_MS = 30000; // after the server has failed a few times in a row
    const TIMEOUT_MS = 8000;
    const MAX_TOASTS = 2; // more kinds than this at once become one summary toast

    const page = root.dataset.page || '';
    const pill = root.querySelector('[data-live-new]');
    const pillText = root.querySelector('[data-live-new-text]');
    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)');
    const number = new Intl.NumberFormat();

    let cursor = null; // { kind: last id seen }; null until the first answer
    let waitingOnPage = 0; // new rows for the list on screen, offered by the pill
    let failures = 0;
    let stopped = false;
    let inFlight = false;
    let timer = null;

    /* Sidebar counts */

    function readCount(badge) {
        return Number(badge.querySelector('.count-num')?.textContent.replace(/[^\d]/g, '') || 0);
    }

    function setCount(badge, value) {
        const label = badge.querySelector('.count-num');
        if (!label) return;
        const before = badge.hidden ? 0 : readCount(badge);
        label.textContent = number.format(value);
        badge.hidden = value <= 0;
        if (value > before && !reducedMotion?.matches) {
            badge.classList.remove('is-bumped');
            void badge.offsetWidth; // restart the animation
            badge.classList.add('is-bumped');
        }
    }

    // A closed group shows what is waiting inside it, in red while an emergency or SOS in it is waiting.
    function updateGroupSums() {
        document.querySelectorAll('[data-group]').forEach((group) => {
            const sum = group.querySelector('[data-group-badge]');
            if (!sum) return;
            const shown = [...group.querySelectorAll('[data-nav-badge]')].filter((badge) => !badge.hidden);
            const urgent = shown.some((badge) => badge.classList.contains('badge-bad') && readCount(badge) > 0);
            sum.classList.toggle('badge-bad', urgent);
            sum.classList.toggle('badge-warn', !urgent);
            setCount(sum, shown.reduce((all, badge) => all + readCount(badge), 0));
        });
    }

    function updateBadges(counts) {
        Object.entries(counts || {}).forEach(([name, value]) => {
            const badge = document.querySelector(`[data-nav-badge="${CSS.escape(name)}"]`);
            if (badge) setCount(badge, Math.max(0, Number(value) || 0));
        });
        updateGroupSums();
    }

    // Other scripts change badges too (emergency-alert.js, messages marked read): keep the group sums in step.
    let sumsQueued = false;
    const watcher = new MutationObserver(() => {
        if (sumsQueued) return;
        sumsQueued = true;
        requestAnimationFrame(() => {
            sumsQueued = false;
            updateGroupSums();
        });
    });
    document.querySelectorAll('[data-group] [data-nav-badge]').forEach((badge) => {
        watcher.observe(badge, { attributes: true, attributeFilter: ['hidden'], childList: true, characterData: true, subtree: true });
    });

    /* What came in */

    function names(entry) {
        const list = entry.items.map((item) => item.name).filter(Boolean);
        const more = entry.total - list.length;
        if (more > 0) return `${list.join(', ')} and ${number.format(more)} more`;
        if (list.length <= 1) return list.join('');
        return `${list.slice(0, -1).join(', ')} and ${list.at(-1)}`;
    }

    function toastFor(entry) {
        if (entry.total === 1 && entry.items[0]) {
            const item = entry.items[0];
            return {
                tone: 'new',
                title: entry.title,
                text: [item.name, item.detail].filter(Boolean).join(' · '),
                href: item.url,
                linkText: 'Open',
            };
        }
        return { tone: 'new', title: entry.title, text: names(entry), href: entry.index_url, linkText: 'View all' };
    }

    function showPill(count) {
        if (!pill || !pillText) return;
        waitingOnPage += count;
        pillText.textContent = `${number.format(waitingOnPage)} new — show`;
        pill.hidden = false;
    }

    function announce(fresh) {
        const entries = Object.entries(fresh || {}).filter(([, entry]) => entry && entry.total > 0);
        if (!entries.length) return;

        const elsewhere = [];
        entries.forEach(([kind, entry]) => {
            if (kind === page && pill) showPill(entry.total);
            else elsewhere.push(entry);
        });

        if (!elsewhere.length || typeof window.adminToast !== 'function') return;

        if (elsewhere.length > MAX_TOASTS) {
            const titles = elsewhere.map((entry) => entry.title.replace(/^New /, '1 new '));
            window.adminToast({
                tone: 'new',
                title: 'New activity',
                text: `${titles.slice(0, -1).join(', ')} and ${titles.at(-1)}`,
                duration: 8000,
            });
            return;
        }
        elsewhere.forEach((entry) => window.adminToast(toastFor(entry)));
    }

    /* Polling */

    function schedule(delay = failures >= 3 ? SLOW_POLL_MS : POLL_MS) {
        clearTimeout(timer);
        timer = null;
        if (stopped || document.hidden) return;
        timer = setTimeout(poll, delay);
    }

    async function poll() {
        if (stopped || inFlight) return;
        inFlight = true;

        const url = new URL(root.dataset.url, window.location.href);
        Object.entries(cursor || {}).forEach(([kind, id]) => url.searchParams.set(`after[${kind}]`, String(id)));

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
                signal: AbortSignal.timeout?.(TIMEOUT_MS),
            });
            // Signed out or no longer an admin: stop quietly, the next page load will sort it out.
            if ([401, 403, 419].includes(response.status)) {
                stopped = true;
                return;
            }
            if (!response.ok) throw new Error(String(response.status));

            const data = await response.json();
            const first = cursor === null;
            cursor = { ...(cursor || {}), ...(data.cursor || {}) };
            updateBadges(data.counts);
            if (!first) announce(data.new);
            failures = 0;
        } catch {
            failures += 1; // try again later; nothing to show the admin for a missed check
        } finally {
            inFlight = false;
            schedule();
        }
    }

    pill?.addEventListener('click', () => {
        pill.disabled = true;
        window.location.reload();
    });

    document.querySelectorAll('[data-nav-badge]').forEach((badge) => {
        badge.addEventListener('animationend', () => badge.classList.remove('is-bumped'));
    });
    document.querySelectorAll('[data-group-badge]').forEach((badge) => {
        badge.addEventListener('animationend', () => badge.classList.remove('is-bumped'));
    });

    // Only while the tab is in front; coming back checks straight away.
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearTimeout(timer);
            timer = null;
        } else if (!inFlight) {
            poll();
        }
    });

    poll();
})();
