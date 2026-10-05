(() => {
    const mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    const REFRESH_MS = 5000;
    const COLORS = { active: '#16a34a', responding: '#f97316', emergency: '#dc2626', offline: '#94a3b8' };
    const LABELS = { active: 'Active', responding: 'Responding', emergency: 'Emergency', offline: 'Offline' };
    const RANK = { emergency: 0, responding: 1, active: 2, offline: 3 };

    const $ = (id) => document.getElementById(id);
    const els = {
        search: $('gobiker-search'), status: $('status-filter'), barangay: $('barangay-filter'),
        list: $('rider-list'), listEmpty: $('rider-empty'), riderCount: $('rider-count'),
        overlay: $('map-overlay'), error: $('map-error'), errorText: $('map-error-text'),
        note: $('map-note'), chip: $('live-chip'), updated: $('last-refresh'),
    };

    const map = L.map(mapEl).setView([15.9528, 120.2155], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const markers = new Map();
    let riders = [];
    let loaded = false;
    let timer = null;
    let controller = null;
    let noteTimer = null;

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
        return `<div class="gb-pop">
            <strong>${esc(r.name)} <em class="s-text s-${esc(r.status)}">${esc(status)}</em></strong>
            <dl>
                <dt>Barangay</dt><dd>${esc(r.designated_barangay || 'Not assigned')}</dd>
                <dt>Location</dt><dd>${hasPos(r) ? `${r.latitude.toFixed(5)}, ${r.longitude.toFixed(5)}` : 'Unavailable'}</dd>
                <dt>Active since</dt><dd>${esc(time(r.active_since, { hour: '2-digit', minute: '2-digit' }) || 'Not active')}</dd>
                <dt>Time active</dt><dd>${esc(r.time_active)}</dd>
                <dt>Last seen</dt><dd>${esc(ago(r.last_seen_at))}</dd>
            </dl>
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
        els.listEmpty.hidden = visible.length > 0 || !loaded;
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
        if (loaded) setOverlay('none');
    }

    function fit() {
        const points = riders.filter(matches).filter(hasPos).map((r) => [r.latitude, r.longitude]);
        if (points.length) map.fitBounds(points, { padding: [40, 40], maxZoom: 15 });
        else notice('No GoBikers with a known location to show.');
    }

    async function load() {
        controller?.abort();
        controller = new AbortController();
        try {
            const res = await fetch('/admin/locations', {
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
        } catch (err) {
            if (err.name === 'AbortError') return;
            els.chip.dataset.state = 'error';
            els.updated.textContent = 'Connection lost';
            els.errorText.textContent = loaded
                ? 'Live updates are paused. Retrying every 5 seconds.'
                : 'Unable to load GoBiker locations.';
            els.error.hidden = false;
            if (!loaded) setOverlay('none');
        }
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
    $('fit-map').addEventListener('click', fit);
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
        map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 15), { duration: 0.6 });
        marker.openPopup();
        if (window.innerWidth < 1180) mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { load(); schedule(); } });

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
})();