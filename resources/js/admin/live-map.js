(() => {
    const mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    const REFRESH_MS = 5000;
    const LOCATIONS_URL = mapEl.closest('[data-locations-url]')?.dataset.locationsUrl ?? '/admin/locations';
    const COLORS = { active: '#16a34a', responding: '#eab308', emergency: '#dc2626', offline: '#94a3b8' };
    const LABELS = { active: 'Active', responding: 'Responding', emergency: 'Emergency', offline: 'Offline' };
    const RANK = { emergency: 0, responding: 1, active: 2, offline: 3 };
    /*
     * Residents' SOS requests: red while nobody has answered, yellow once an admin has. Resolved
     * ones are not sent to the map at all, so the pin disappears when the emergency is over.
     * Checked about once a second, far more often than the GoBiker dots.
     */
    const SOS_POLL_MS = 1000;
    const section = mapEl.closest('[data-sos-url]');
    const SOS_URL = section?.dataset.sosUrl;
    const SOS_STATUS_URL = section?.dataset.sosStatusUrl;
    const SOS_GOBIKERS_URL = section?.dataset.sosGobikersUrl;
    const SOS_STATE_CLASS = { sent: 'is-waiting', responding: 'is-responding' };
    // Decorative line icons for the report rows; the label beside each one carries the meaning.
    const SOS_ICONS = {
        barangay: '<path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
        address: '<path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        landmark: '<path d="M4 21h16M6 21V8l6-4 6 4v13M10 21v-4h4v4"/>',
        details: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.6h.01"/>',
        sent: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    };
    // "Responding" only when a GoBiker was actually sent: an admin can take a request on without
    // assigning anyone yet, and the map must not claim somebody is on the way.
    const SOS_LABELS = { sent: 'SOS: waiting', assigned: 'SOS: responding', handled: 'SOS: admin handling' };
    const SOS_CLASS = { sent: 's-sent', responding: 's-sos-responding' };

    const $ = (id) => document.getElementById(id);
    const els = {
        search: $('gobiker-search'), status: $('status-filter'), barangay: $('barangay-filter'),
        list: $('rider-list'), listEmpty: $('rider-empty'), riderCount: $('rider-count'),
        overlay: $('map-overlay'), error: $('map-error'), errorText: $('map-error-text'),
        note: $('map-note'), chip: $('live-chip'), updated: $('last-refresh'),
    };

    // Canvas draws hundreds of GoBiker dots much faster than one SVG element each.
    const map = L.map(mapEl, { preferCanvas: true }).setView([16.05, 120.3], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const markers = new Map();
    const sosMarkers = new Map();
    let riders = [];
    let loaded = false;
    let timer = null;
    let controller = null;
    let noteTimer = null;
    let sosInFlight = false;
    let sosQueued = false;
    let sosTicker = null;
    let sosFallbackTimer = null;
    let pendingFocus = new URLSearchParams(window.location.search).get('focus');

    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    })[c]);

    const hasPos = (r) => Number.isFinite(r.latitude) && Number.isFinite(r.longitude);

    const ago = (iso) => {
        if (!iso) return 'Never seen';
        const s = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
        if (s < 10) return 'Just now';
        if (s < 60) return `${s}s ago`;
        if (s < 3600) return `${Math.floor(s / 60)}m ago`;
        if (s < 86400) return `${Math.floor(s / 3600)}h ago`;
        return `${Math.floor(s / 86400)}d ago`;
    };

    const time = (iso, opts) => (iso ? new Date(iso).toLocaleTimeString([], opts) : null);

    function setOverlay(state) {
        els.overlay.hidden = state === 'none';
        els.overlay.querySelectorAll('[data-view]').forEach((n) => { n.hidden = n.dataset.view !== state; });
    }

    function notice(message, ms = 5000) {
        els.note.textContent = message;
        els.note.hidden = false;
        clearTimeout(noteTimer);
        noteTimer = setTimeout(() => { els.note.hidden = true; }, ms);
    }

    function popup(r) {
        const status = LABELS[r.status] || 'Unknown';
        const mobile = r.mobile
            ? `<a href="tel:${esc(r.mobile)}">${esc(r.mobile)}</a>`
            : 'Not provided';

        const respondBtn = `<button type="button" class="btn btn-sm gb-act gb-act-resp" data-action="responding" data-location="${esc(r.location_id)}">Responding</button>`;
        const resolveBtn = `<button type="button" class="btn btn-sm gb-act gb-act-resolve" data-action="active" data-location="${esc(r.location_id)}">Resolve</button>`;
        const actions = r.status === 'emergency'
            ? `<div class="gb-actions">${respondBtn}${resolveBtn}</div>`
            : r.status === 'responding'
                ? `<div class="gb-actions">${resolveBtn}</div>`
                : '';

        return `<div class="gb-pop">
            <strong>${esc(r.name)} <em class="s-text s-${esc(r.status)}">${esc(status)}</em></strong>
            <dl>
                <dt>Mobile</dt><dd>${mobile}</dd>
                <dt>Barangay</dt><dd>${esc(r.designated_barangay || 'Not assigned')}</dd>
                <dt>Location</dt><dd>${hasPos(r) ? `${r.latitude.toFixed(5)}, ${r.longitude.toFixed(5)}` : 'Unavailable'}</dd>
                <dt>Active since</dt><dd>${esc(time(r.active_since, { hour: '2-digit', minute: '2-digit' }) || 'Not active')}</dd>
                <dt>Time active</dt><dd>${esc(r.time_active)}</dd>
                <dt>Last seen</dt><dd>${esc(ago(r.last_seen_at))}</dd>
            </dl>
            ${actions}
        </div>`;
    }

    function matches(r) {
        const q = els.search.value.trim().toLowerCase();
        return (!q || r.name.toLowerCase().includes(q))
            && (els.status.value === 'all' || r.status === els.status.value)
            && (els.barangay.value === 'all' || r.designated_barangay === els.barangay.value);
    }

    function syncBarangays() {
        const names = [...new Set(riders.map((r) => r.designated_barangay).filter(Boolean))].sort();
        const selected = els.barangay.value;
        els.barangay.innerHTML = '<option value="all">All barangays</option>'
            + names.map((n) => `<option value="${esc(n)}">${esc(n)}</option>`).join('');
        els.barangay.value = names.includes(selected) ? selected : 'all';
    }

    function renderList(visible) {
        const top = els.list.scrollTop;
        const focusedId = document.activeElement?.dataset?.id;

        els.riderCount.textContent = visible.length;
        els.list.removeAttribute('aria-busy'); // the placeholder rows are replaced just below
        // With no GoBikers at all the map overlay explains it; this message is only for filters.
        els.listEmpty.hidden = visible.length > 0 || !loaded || riders.length === 0;
        els.list.innerHTML = visible.map((r) => `
            <li><button type="button" class="rider" data-id="${esc(r.id)}">
                <span class="status-dot s-${esc(r.status)}"></span>
                <span class="rider-main"><b>${esc(r.name)}</b><small>${esc(r.designated_barangay || 'No barangay assigned')}</small></span>
                <span class="rider-meta"><em class="s-text s-${esc(r.status)}">${esc(LABELS[r.status] || 'Unknown')}</em><small>${esc(ago(r.last_seen_at))}</small></span>
            </button></li>`).join('');

        els.list.scrollTop = top;
        if (focusedId) els.list.querySelector(`[data-id="${CSS.escape(focusedId)}"]`)?.focus({ preventScroll: true });
    }

    function render() {
        const visible = riders.filter(matches)
            .sort((a, b) => (RANK[a.status] ?? 9) - (RANK[b.status] ?? 9) || a.name.localeCompare(b.name));
        const visibleIds = new Set(visible.map((r) => String(r.id)));
        const allIds = new Set(riders.map((r) => String(r.id)));

        visible.filter(hasPos).forEach((r) => {
            const id = String(r.id);
            const color = COLORS[r.status] || COLORS.offline;
            const style = { radius: r.status === 'emergency' ? 11 : 9, color: '#fff', weight: 2, fillColor: color, fillOpacity: 1 };
            let marker = markers.get(id);
            if (!marker) {
                marker = L.circleMarker([r.latitude, r.longitude], style).addTo(map);
                marker.bindPopup(popup(r));
                markers.set(id, marker);
            } else {
                marker.setLatLng([r.latitude, r.longitude]);
                marker.setStyle(style);
                marker.setPopupContent(popup(r));
                if (!map.hasLayer(marker)) marker.addTo(map);
            }
        });

        markers.forEach((marker, id) => {
            if (!visibleIds.has(id) && map.hasLayer(marker)) map.removeLayer(marker);
            if (!allIds.has(id)) markers.delete(id);
        });

        const counts = { total: riders.length, active: 0, responding: 0, emergency: 0, offline: 0 };
        riders.forEach((r) => { if (r.status in counts) counts[r.status] += 1; });
        Object.entries(counts).forEach(([key, value]) => {
            const el = $(`${key}-count`);
            if (el) el.textContent = value;
        });

        renderList(visible);
        if (loaded) setOverlay(riders.length ? 'none' : 'empty');
    }

    const initials = (name) => String(name ?? '').trim().split(/\s+/).filter(Boolean)
        .slice(0, 2).map((part) => part[0].toUpperCase()).join('') || '?';

    /* Someone's photo, or their initials in the same circle when they have not added one. */
    function face(name, photo, size) {
        const style = size ? ` style="--avatar-size: ${size}px"` : '';

        return photo
            ? `<img class="avatar avatar-photo" src="${esc(photo)}" alt="${esc(name)}" loading="lazy"${style}>`
            : `<span class="avatar" aria-hidden="true"${style}>${esc(initials(name))}</span>`;
    }

    /* Who is on their way, so the admin can see at a glance that someone is actually going. */
    function responderChip(p) {
        if (!p.assigned_gobiker) return '';
        const where = p.assigned_gobiker_barangay ? ` · ${esc(p.assigned_gobiker_barangay)}` : '';

        return `<div class="sos-responder">
            ${face(p.assigned_gobiker, p.assigned_gobiker_photo)}
            <span class="sos-responder-main">
                <b>${esc(p.assigned_gobiker)}</b>
                <small><em class="s-text s-sos-responding">Responding</em>${where}</small>
            </span>
        </div>`;
    }

    /* The barangay filter and the GoBiker list; both are filled in once the popup opens. */
    function assignBlock(p, action, label) {
        return `<div class="sos-assign">
            <label>Barangay
                <select class="sos-barangay" data-sos-barangay="${esc(p.id)}"><option value="">Loading…</option></select>
            </label>
            <label>Send a GoBiker
                <select class="sos-gobiker" data-sos-gobikers="${esc(p.id)}"><option value="">Loading GoBikers…</option></select>
            </label>
            <button type="button" class="btn btn-sm gb-act gb-act-resp" data-sos-action="${esc(action)}" data-sos="${esc(p.id)}">${esc(label)}</button>
        </div>`;
    }

    /* What the pin's headline says: who, if anyone, is actually on the way. */
    function sosHeadline(p) {
        if (p.status === 'sent') return SOS_LABELS.sent;

        return p.assigned_gobiker ? SOS_LABELS.assigned : SOS_LABELS.handled;
    }

    /* One line of the report: its icon, the muted label, then what the resident told us. */
    function fact(name, label, value) {
        const icon = `<svg class="sos-fact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${SOS_ICONS[name]}</svg>`;

        return `<dt>${icon}${esc(label)}</dt><dd>${esc(value)}</dd>`;
    }

    function sosPopup(p) {
        const mobile = p.mobile
            ? `<a class="sos-pop-mobile" href="tel:${esc(p.mobile)}">${esc(p.mobile)}</a>`
            : '<span class="sos-pop-mobile">Not provided</span>';
        const actions = p.status === 'sent'
            ? assignBlock(p, 'responding', 'Responding')
            // Already being answered: the admin can still send someone, or someone nearer.
            : assignBlock(p, 'responding', p.assigned_gobiker ? 'Send another GoBiker' : 'Send a GoBiker');

        return `<div class="gb-pop sos-pop">
            <div class="sos-pop-head">
                ${face(p.resident, p.resident_photo, 48)}
                <div class="sos-pop-who">
                    <p class="sos-pop-name">
                        <b>${esc(p.resident)}</b>
                        <em class="s-text sos-state ${esc(SOS_STATE_CLASS[p.status] || SOS_STATE_CLASS.sent)}">${esc(sosHeadline(p))}</em>
                    </p>
                    ${mobile}
                </div>
            </div>
            <dl class="sos-facts">
                ${fact('barangay', 'Barangay', p.barangay || 'Not given')}
                ${fact('address', 'Address', p.address || 'Not given')}
                ${fact('landmark', 'Landmark', p.landmark || 'Not given')}
                ${p.description ? fact('details', 'Details', p.description) : ''}
                ${fact('sent', 'Sent', ago(p.created_at))}
            </dl>
            ${responderChip(p)}
            ${actions}
        </div>`;
    }

    /* A div marker, not a canvas circle: only an element in the page can be made to pulse. */
    function sosIcon(status) {
        return L.divIcon({
            className: 'sos-pin-wrap',
            html: `<span class="sos-pin ${SOS_CLASS[status] || SOS_CLASS.sent}"></span>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14],
            popupAnchor: [0, -14],
        });
    }

    /*
     * Draws the emergencies that are still open, and takes away any pin that is no longer in the
     * list — which is how a resolved one disappears. Keyed by request id, so removing one leaves
     * every other emergency exactly where it was.
     */
    function renderSos(pins) {
        const ids = new Set(pins.map((p) => String(p.id)));
        pins.forEach((p) => {
            const id = String(p.id);
            // Rebuild the popup only when something changed, so a half-picked GoBiker isn't lost.
            const key = `${p.status}|${p.assigned_gobiker}`;
            let marker = sosMarkers.get(id);
            if (!marker) {
                marker = L.marker([p.latitude, p.longitude], {
                    icon: sosIcon(p.status),
                    // Always above the GoBiker dots: an emergency must never end up underneath one.
                    zIndexOffset: 1000,
                    keyboard: true,
                    title: `${p.resident}: ${sosHeadline(p)}`,
                }).addTo(map);
                marker.bindPopup(sosPopup(p), { minWidth: 268, maxWidth: 340 });
                marker.on('popupopen', () => fillGobikers(id));
                sosMarkers.set(id, marker);
            } else {
                marker.setLatLng([p.latitude, p.longitude]);
                if (marker.sosStatus !== p.status) marker.setIcon(sosIcon(p.status));
                if (marker.sosKey !== key) {
                    marker.setPopupContent(sosPopup(p));
                    // Rebuilding threw away the dropdowns: fill the new ones.
                    if (marker.isPopupOpen()) fillGobikers(id);
                }
            }
            marker.sosStatus = p.status;
            marker.sosKey = key;
        });
        sosMarkers.forEach((marker, id) => {
            if (!ids.has(id)) { map.removeLayer(marker); sosMarkers.delete(id); }
        });
    }

    /*
     * Reloads the open emergencies. Runs about once a second, so it must never overlap itself:
     * two answers landing out of order would put a resolved pin back on the map. A tick arriving
     * while one is in flight is collapsed into a single trailing call.
     */
    async function loadSos() {
        if (!SOS_URL) return;
        if (sosInFlight) {
            sosQueued = true;

            return;
        }
        sosInFlight = true;
        try {
            const res = await fetch(SOS_URL, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
                signal: AbortSignal.timeout(8000),
            });
            if (res.ok) renderSos(await res.json());
        } catch {
            // The GoBiker layer shows the connection error; SOS pins catch up on the next tick.
        } finally {
            sosInFlight = false;
            if (sosQueued) {
                sosQueued = false;
                loadSos();
            }
        }
    }

    /*
     * Its own timer, ten times faster than the GoBiker dots: an emergency appearing or being
     * resolved has to show at once, while the full location list is heavier and can wait.
     * Ticked by a worker because browsers slow page timers right down in a background tab.
     */
    function startSosTimer() {
        if (!SOS_URL) return;
        const fallback = () => {
            sosTicker?.terminate();
            sosTicker = null;
            clearInterval(sosFallbackTimer);
            sosFallbackTimer = setInterval(loadSos, SOS_POLL_MS);
        };
        try {
            sosTicker = new Worker(new URL('./emergency-ticker.js', import.meta.url));
            sosTicker.onmessage = loadSos;
            sosTicker.onerror = fallback;
            sosTicker.postMessage(SOS_POLL_MS);
        } catch {
            // e.g. while `npm run dev` serves scripts from another address
            fallback();
        }
    }

    /* "Juan Dela Cruz · Poblacion · 1.2 km · online" — enough to pick whoever is really nearest. */
    function gobikerLabel(g) {
        const bits = [g.barangay, Number.isFinite(g.distance_km) ? `${g.distance_km} km` : null, g.online ? 'online' : 'offline'];

        return `${g.name} · ${bits.filter(Boolean).join(' · ')}`;
    }

    function fillBarangays(id, data, chosen) {
        const select = document.querySelector(`[data-sos-barangay="${CSS.escape(id)}"]`);
        if (!select) return;
        const selected = chosen ?? data.barangay ?? 'all';
        select.innerHTML = '<option value="all">All barangays</option>'
            + (data.barangays ?? []).map((n) => `<option value="${esc(n)}">${esc(n)}</option>`).join('');
        select.value = [...select.options].some((o) => o.value === selected) ? selected : 'all';
    }

    /**
     * The GoBikers the admin may send. Called when the popup opens (no barangay: the server
     * defaults to the emergency's own) and again whenever the admin changes the barangay.
     */
    async function fillGobikers(id, barangay) {
        const select = document.querySelector(`[data-sos-gobikers="${CSS.escape(id)}"]`);
        if (!select || (barangay === undefined && select.dataset.loaded)) return;
        select.innerHTML = '<option value="">Loading GoBikers…</option>';
        const base = SOS_GOBIKERS_URL.replace('__ID__', encodeURIComponent(id));
        const url = barangay === undefined ? base : `${base}?barangay=${encodeURIComponent(barangay)}`;
        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error(String(res.status));
            const data = await res.json();
            fillBarangays(id, data, barangay);
            select.innerHTML = data.gobikers.length
                ? '<option value="">Choose a GoBiker</option>' + data.gobikers.map((g) => `<option value="${esc(g.id)}">${esc(gobikerLabel(g))}</option>`).join('')
                : `<option value="">No GoBiker covers ${esc(barangay === 'all' ? 'the town' : (barangay ?? data.barangay ?? 'this barangay'))}</option>`;
            select.dataset.loaded = '1';
        } catch {
            select.innerHTML = '<option value="">Could not load GoBikers</option>';
        }
    }

    async function load() {
        controller?.abort();
        controller = new AbortController();
        try {
            const res = await fetch(LOCATIONS_URL, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            if (res.status === 401 || res.status === 419) { window.location.reload(); return; }
            if (!res.ok) throw new Error(String(res.status));

            riders = await res.json();
            loaded = true;
            els.error.hidden = true;
            els.chip.dataset.state = 'live';
            els.updated.textContent = `Updated ${time(new Date().toISOString(), { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
            syncBarangays();
            render();
            await loadSos();
            if (pendingFocus !== null) {
                focusLocation(pendingFocus);
                pendingFocus = null;
            }
        } catch (err) {
            if (err.name === 'AbortError') return;
            els.chip.dataset.state = 'error';
            els.updated.textContent = 'Connection lost';
            els.errorText.textContent = loaded
                ? 'Live updates are paused. Retrying every 5 seconds.'
                : 'Unable to load GoBiker locations.';
            els.error.hidden = false;
            if (!loaded) {
                setOverlay('none');
                // Nothing will arrive: don't leave placeholders shimmering next to the error.
                ['total', 'active', 'responding', 'emergency', 'offline'].forEach((key) => { $(`${key}-count`).textContent = '–'; });
                els.list.replaceChildren();
                els.list.removeAttribute('aria-busy');
            }
        }
    }

    function flyToMarker(marker) {
        map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 12), {
            duration: 1.6,
            easeLinearity: 0.15,
        });
        marker.openPopup();
        if (window.innerWidth < 1180) mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /* "Show on map" from the emergency banner (?focus=<location id> or the gobike:show-location event). */
    function focusLocation(locationId) {
        const rider = riders.find((r) => Number(r.location_id) === Number(locationId));
        if (!rider) return false;

        // A filter must never hide the GoBiker the admin asked to see.
        if (!matches(rider)) {
            els.search.value = '';
            els.status.value = 'all';
            els.barangay.value = 'all';
            render();
        }

        const marker = markers.get(String(rider.id));
        if (marker) flyToMarker(marker);
        else notice('This GoBiker has not shared a location yet.');
        return true;
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            if (!document.hidden) await load();
            schedule();
        }, REFRESH_MS);
    }

    /* Events */
    ['input', 'change'].forEach((type) => {
        [els.search, els.status, els.barangay].forEach((el) => el.addEventListener(type, render));
    });
    $('locate-map').addEventListener('click', () => map.locate({ setView: true, maxZoom: 16 }));
    map.on('locationerror', () => notice('We could not get your location. Allow location access in your browser and try again.'));
    $('map-retry').addEventListener('click', () => { load(); schedule(); });
    $('clear-filters')?.addEventListener('click', () => {
        els.search.value = '';
        els.status.value = 'all';
        els.barangay.value = 'all';
        render();
    });
    els.list.addEventListener('click', (e) => {
        const item = e.target.closest('[data-id]');
        const marker = item && markers.get(item.dataset.id);
        if (!marker) { if (item) notice('This GoBiker has not shared a location yet.'); return; }
        flyToMarker(marker);
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { load(); schedule(); } });
    document.addEventListener('gobike:show-location', (e) => {
        if (loaded) focusLocation(e.detail.locationId);
        else pendingFocus = e.detail.locationId;
    });
    // The emergency banner marked someone as responding: show it on the map right away.
    document.addEventListener('gobike:emergencies-changed', (e) => {
        if (e.detail?.source !== 'map') load();
    });

    /* The admin picked another barangay: reload the GoBikers from there. */
    document.addEventListener('change', (e) => {
        const select = e.target.closest('.gb-pop [data-sos-barangay]');
        if (select) fillGobikers(select.dataset.sosBarangay, select.value);
    });

    /* Resident SOS popup: send a GoBiker and mark it as responding. Only that GoBiker resolves it. */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.gb-pop [data-sos-action]');
        if (!btn) return;
        const id = btn.dataset.sos;
        const gobikerId = document.querySelector(`[data-sos-gobikers="${CSS.escape(id)}"]`)?.value;
        if (!gobikerId) { notice('Choose the GoBiker who will respond first.'); return; }

        btn.disabled = true;
        try {
            const res = await fetch(SOS_STATUS_URL.replace('__ID__', encodeURIComponent(id)), {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ status: 'responding', gobiker_id: Number(gobikerId) }),
            });
            const body = await res.json().catch(() => ({}));
            if (!res.ok) {
                notice(body.message || 'Could not update the SOS. Please try again.');
                btn.disabled = false;
                return;
            }
            window.adminToast?.({ text: `Marked as responding. ${body.assigned_gobiker || 'The GoBiker'} was notified.` });
            document.dispatchEvent(new CustomEvent('gobike:emergencies-changed', { detail: { source: 'map' } }));
            await loadSos();
        } catch {
            notice('Could not update the SOS. Please try again.');
            btn.disabled = false;
        }
    }, true);

    /* Admin actions inside the popup: "Responding" / "Resolve" */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.gb-pop [data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        if (action === 'active') {
            const yes = await window.adminConfirm({
                title: 'Mark this emergency as resolved?',
                text: 'The alert will be cleared from the map.',
                ok: 'Resolve',
                tone: 'ok',
            });
            if (!yes) return;
        }

        btn.disabled = true;
        try {
            const res = await fetch(`${LOCATIONS_URL}/${encodeURIComponent(btn.dataset.location)}/status`, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ status: action }),
            });
            if (!res.ok) throw new Error(String(res.status));
            window.adminToast?.({ text: action === 'responding' ? 'Marked as responding.' : 'Emergency resolved.' });
            // Lets the emergency banner stop the alarm without waiting for its next check.
            document.dispatchEvent(new CustomEvent('gobike:emergencies-changed', { detail: { source: 'map' } }));
            await load();
        } catch {
            notice('Could not update the status. Please try again.');
            btn.disabled = false;
        }
    }, true);

    const clock = $('live-clock');
    const tick = () => {
        if (!clock) return;
        const now = new Date();
        clock.dateTime = now.toISOString();
        clock.textContent = now.toLocaleString([], {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    };
    tick();
    setInterval(tick, 1000);

    setOverlay('loading');
    load().then(schedule);
    startSosTimer();
})();