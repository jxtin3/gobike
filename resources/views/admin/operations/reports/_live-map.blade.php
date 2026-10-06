<section class="dashboard-map-section" aria-labelledby="dashboard-map-title">
    <header class="dashboard-map-heading">
        <div>
            <span class="analytics-eyebrow">Live operations</span>
            <h2 id="dashboard-map-title">GoBiker locations</h2>
            <p>Current field activity and location updates.</p>
        </div>
        <div class="dashboard-map-status">
            <time class="live-clock" id="live-clock" datetime="" aria-live="polite"></time>
            <span class="live-chip" id="live-chip" data-state="connecting" role="status">
                <i class="live-dot"></i><span id="last-refresh">Connecting…</span>
            </span>
        </div>
    </header>

    <div class="stat-grid" aria-label="Current GoBiker status">
        <div class="stat-card"><span class="stat-icon"><x-admin.icon name="users" /></span><div><div class="label">Total GoBikers</div><div class="value" id="total-count">–</div></div></div>
        <div class="stat-card"><span class="stat-icon t-ok"><x-admin.icon name="activity" /></span><div><div class="label">Active</div><div class="value" id="active-count">–</div></div></div>
        <div class="stat-card"><span class="stat-icon t-bad"><x-admin.icon name="alert-triangle" /></span><div><div class="label">Emergency</div><div class="value" id="emergency-count">–</div></div></div>
        <div class="stat-card"><span class="stat-icon t-off"><x-admin.icon name="clock" /></span><div><div class="label">Offline</div><div class="value" id="offline-count">–</div></div></div>
    </div>

    <div class="map-layout">
        <section class="card map-card" aria-label="Live GoBiker map">
            <div class="map-toolbar">
                <label class="tool-field grow">
                    <span>Search</span>
                    <span class="tool-input"><x-admin.icon name="search" /><input id="gobiker-search" type="search" placeholder="Search GoBiker by name" autocomplete="off"></span>
                </label>
                <label class="tool-field">
                    <span>Status</span>
                    <select id="status-filter">
                        <option value="all">All statuses</option>
                        <option value="active">Active</option>
                        <option value="responding">Responding</option>
                        <option value="emergency">Emergency</option>
                        <option value="offline">Offline</option>
                    </select>
                </label>
                <label class="tool-field">
                    <span>Barangay</span>
                    <select id="barangay-filter"><option value="all">All barangays</option></select>
                </label>
                <button id="locate-map" class="btn btn-secondary" type="button"><x-admin.icon name="crosshair" /> Locate me</button>
            </div>

            <div class="map-stage">
                <div id="map" role="application" aria-label="Map of GoBiker locations"></div>
                <div class="map-overlay" id="map-overlay">
                    <div data-view="loading"><span class="spinner"></span><b>Loading live locations</b></div>
                    <div data-view="empty" hidden>
                        <x-admin.icon name="map" />
                        <b>No GoBikers on the map yet</b>
                        <p>They appear here as soon as they start a ronda in the mobile app.</p>
                    </div>
                </div>
            </div>

            <p class="map-note" id="map-note" role="status" hidden></p>
            <div class="map-error" id="map-error" role="alert" hidden>
                <x-admin.icon name="alert-circle" />
                <span id="map-error-text">Unable to refresh live locations.</span>
                <button type="button" class="btn btn-secondary btn-sm" id="map-retry">Retry now</button>
            </div>
        </section>

        <aside class="card rider-panel" aria-label="GoBiker list">
            <div class="legend rider-legend" aria-label="GoBiker statuses">
                <span><i class="status-dot s-active"></i>Active</span>
                <span><i class="status-dot s-emergency"></i>Emergency</span>
                <span><i class="status-dot s-responding"></i>Responding</span>
                <span><i class="status-dot s-offline"></i>Offline</span>
            </div>
            <header><h2>GoBikers</h2><span class="count" id="rider-count">0</span></header>
            <ul id="rider-list"></ul>
            <div class="empty" id="rider-empty" hidden>
                <x-admin.icon name="search" />
                <b>No GoBikers match</b>
                <p>Try a different name, status or barangay.</p>
                <button type="button" class="btn btn-secondary btn-sm" id="clear-filters">Clear filters</button>
            </div>
        </aside>
    </div>
</section>

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush
