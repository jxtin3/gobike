/*
 * Loading placeholders.
 *
 * 1. Moving between pages: a link or GET form marked data-skeleton="<type>" lays a placeholder shaped like the next
 *    page over the content, if the server takes longer than a moment. The page underneath is only hidden, never
 *    removed, so charts, the map and half-typed fields keep their size and state if the navigation is cancelled.
 *    Where it appears:
 *      - links and forms inside [data-list] (pager, tabs, filters): only the list, so the header and search stay
 *      - links in the sidebar: the whole page body, with a title bar
 * 2. Pictures: a tile shimmers until its image has loaded.
 * Placeholders live in <template data-skeleton-template> (partials/skeletons.blade.php).
 */
const DELAY = 180; // faster than this and a placeholder would only flash
const GIVE_UP = 15000; // downloads and the like never leave the page: put it back

let current = null;

const template = (type) => document.querySelector(`template[data-skeleton-template="${type}"]`);

/** Which part of the page the placeholder covers, and which placeholders to draw there. */
function plan(type, source) {
    const main = document.getElementById('main');
    if (!main) return null;
    const inPage = main.contains(source);

    if (type === 'rows') {
        const list = document.querySelector('[data-list]');
        return list ? { region: list, parts: ['rows'] } : null;
    }
    if (type === 'dashboard' && inPage) {
        const body = document.querySelector('[data-analytics-dashboard]');
        return body ? { region: body, parts: ['dashboard'] } : null;
    }
    return { region: main, parts: ['head', type] };
}

function disarm() {
    if (!current) return;
    const { timer, giveUp, guard, layer, region, link, previous } = current;
    clearTimeout(timer);
    clearTimeout(giveUp);
    window.removeEventListener('beforeunload', guard);
    if (layer) {
        layer.remove();
        region.classList.remove('sk-active');
        region.removeAttribute('aria-busy');
    }
    // Put the sidebar's "you are here" marker back.
    if (link && previous !== undefined) {
        link.setAttribute('aria-current', previous);
        current.otherLink?.setAttribute('aria-current', 'page');
    }
    current = null;
}

function arm(type, source) {
    disarm();
    const wanted = plan(type, source);
    if (!wanted || wanted.parts.some((part) => !template(part))) return;

    // A page can refuse to be left ("unsaved changes"). Its handler runs before this one, so if it objected,
    // the navigation may never happen and there must be no placeholder.
    const guard = (e) => { if (e.defaultPrevented || e.returnValue) disarm(); };
    window.addEventListener('beforeunload', guard);

    current = {
        guard,
        region: wanted.region,
        timer: setTimeout(() => show(wanted, source), DELAY),
        giveUp: setTimeout(disarm, GIVE_UP),
    };
}

function show({ region, parts }, source) {
    if (!current || current.layer) return;

    const layer = document.createElement('div');
    layer.className = 'sk-layer';
    layer.setAttribute('aria-hidden', 'true');
    parts.forEach((part) => layer.append(template(part).content.cloneNode(true)));

    region.classList.add('sk-active');
    region.setAttribute('aria-busy', 'true');
    region.append(layer);
    current.layer = layer;

    // The sidebar shows where you are going, not where you were.
    const link = source.closest?.('.side-link');
    if (link) {
        current.link = link;
        current.previous = link.getAttribute('aria-current') ?? 'false';
        current.otherLink = document.querySelector('.side-link[aria-current="page"]');
        current.otherLink?.setAttribute('aria-current', 'false');
        link.setAttribute('aria-current', 'page');
    }
}

function eligibleLink(link, e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.hasAttribute('download') || link.closest('dialog, [popover]')) return false;
    const url = new URL(link.href, location.href);
    if (!/^https?:$/.test(url.protocol) || url.origin !== location.origin) return false;
    // Same page (a hash jump, or the tab you are already on), and files (?format=csv) never leave the page.
    if (url.pathname === location.pathname && url.search === location.search) return false;
    return !url.searchParams.has('format');
}

document.addEventListener('click', (e) => {
    const link = e.target.closest?.('a[data-skeleton]');
    if (!link || !eligibleLink(link, e)) return;
    arm(link.dataset.skeleton, link);
    // Another handler may still cancel the click a moment later.
    setTimeout(() => { if (e.defaultPrevented) disarm(); }, 0);
});

// Runs after admin.js asked for confirmation, so a submit that is still waiting for a "Yes" is skipped.
window.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.skeleton || e.defaultPrevented) return;
    const method = (e.submitter?.getAttribute('formmethod') || form.getAttribute('method') || 'get').toLowerCase();
    const target = e.submitter?.getAttribute('formtarget') || form.target;
    if (method !== 'get' || (target && target !== '_self')) return;
    arm(form.dataset.skeleton, form);
});

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') disarm(); });
window.addEventListener('pageshow', disarm); // back/forward cache brings the page back as it was

/* ---------- Pictures and article images: placeholder until loaded ---------- */

document.querySelectorAll('.picture-card-media, .picture-detail-media, img.detail-image').forEach((box) => {
    const media = box.matches('img') ? box : box.querySelector('img, video');
    if (!media) return;
    const done = () => box.classList.add('is-loaded');
    const isImage = media.tagName === 'IMG';
    if (isImage ? media.complete : media.readyState >= 2) {
        done();
        return;
    }
    media.addEventListener(isImage ? 'load' : 'loadeddata', done, { once: true });
    media.addEventListener('error', done, { once: true });
});
