(function () {
    const mapElement = document.getElementById('map');

    if (!mapElement || typeof L === 'undefined') return;

    const map = L.map(mapElement).setView([15.9528, 120.2155], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const markers = new Map();
    const colors = { active: '#22c55e', responding: '#f97316', emergency: '#ef4444', offline: '#94a3b8' };
    let latestLocations = [];

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    })[character]);

    function popup(item) {
        const status = item.status.charAt(0).toUpperCase() + item.status.slice(1);
        const lastUpdated = item.last_seen_at ? new Date(item.last_seen_at).toLocaleTimeString() : 'Never';
        const activeSince = item.active_since ? new Date(item.active_since).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Not active';

        return `<div class="gobiker-popup">
            <strong>GO-BIKER INFORMATION</strong>
            <dl>
                <dt>Name</dt><dd>${escapeHtml(item.name)}</dd>
                <dt>Designated Barangay</dt><dd>${escapeHtml(item.designated_barangay || 'Not assigned')}</dd>
                <dt>Current GPS Location</dt><dd>${item.latitude ?? 'Unavailable'}, ${item.longitude ?? 'Unavailable'}</dd>
                <dt>Status</dt><dd><span class="status status-${item.status}">${status}</span></dd>
                <dt>Active Since</dt><dd>${activeSince}</dd>
                <dt>Time Active</dt><dd>${item.time_active}</dd>
                <dt>Last Updated</dt><dd>${lastUpdated}</dd>
            </dl>
        </div>`;
    }

    function matchesFilters(item) {
        const search = document.getElementById('gobiker-search').value.trim().toLowerCase();
        const status = document.getElementById('status-filter').value;
        const barangay = document.getElementById('barangay-filter').value;
        return (!search || item.name.toLowerCase().includes(search))
            && (status === 'all' || item.status === status)
            && (barangay === 'all' || item.designated_barangay === barangay);
    }

    function render() {
        const visible = latestLocations.filter(matchesFilters);
        const visibleIds = new Set(visible.map((item) => String(item.id)));
        const counts = { total: latestLocations.length, active: 0, responding: 0, offline: 0 };

        latestLocations.forEach((item) => {
            if (item.status === 'active') counts.active += 1;
            if (item.status === 'responding') counts.responding += 1;
            if (item.status === 'offline') counts.offline += 1;
        });

        visible.forEach((item) => {
            if (item.latitude === null || item.longitude === null) return;
            const id = String(item.id);
            const position = [item.latitude, item.longitude];
            const color = colors[item.status] || colors.offline;
            let marker = markers.get(id);

            if (!marker) {
                marker = L.circleMarker(position, { radius: 9, color, fillColor: color, fillOpacity: .9, weight: 2 }).addTo(map);
                markers.set(id, marker);
            } else {
                marker.setLatLng(position);
                marker.setStyle({ color, fillColor: color });
            }
            marker.bindPopup(popup(item));
        });

        markers.forEach((marker, id) => {
            if (!visibleIds.has(id)) map.removeLayer(marker);
            else if (!map.hasLayer(marker)) marker.addTo(map);
        });

        document.getElementById('total-count').textContent = counts.total;
        document.getElementById('active-count').textContent = counts.active;
        document.getElementById('responding-count').textContent = counts.responding;
        document.getElementById('offline-count').textContent = counts.offline;
        document.getElementById('last-refresh').textContent = `Updated ${new Date().toLocaleTimeString()}`;
    }

    function loadLocations() {
        fetch('/admin/locations', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => { if (!response.ok) throw new Error(`Location request failed: ${response.status}`); return response.json(); })
            .then((items) => {
                latestLocations = items;
                const barangays = [...new Set(items.map((item) => item.designated_barangay).filter(Boolean))].sort();
                const filter = document.getElementById('barangay-filter');
                const selected = filter.value;
                filter.innerHTML = '<option value="all">All barangays</option>' + barangays.map((barangay) => `<option value="${escapeHtml(barangay)}">${escapeHtml(barangay)}</option>`).join('');
                filter.value = barangays.includes(selected) ? selected : 'all';
                render();
            })
            .catch(() => { document.getElementById('map-error').textContent = 'Unable to refresh live locations.'; });
    }

    ['gobiker-search', 'status-filter', 'barangay-filter'].forEach((id) => document.getElementById(id).addEventListener('input', render));
    document.getElementById('fit-map').addEventListener('click', () => {
        const points = latestLocations.filter((item) => item.latitude !== null).map((item) => [item.latitude, item.longitude]);
        if (points.length) map.fitBounds(points, { padding: [30, 30], maxZoom: 15 });
    });
    document.getElementById('locate-map').addEventListener('click', () => map.locate({ setView: true, maxZoom: 16 }));
    loadLocations();
    setInterval(loadLocations, 5000);
})();
