/*
 * Emergency alerts on every admin page (markup: admin/partials/emergency-alert.blade.php).
 * Polls for GoBiker emergencies and residents' SOS requests nobody has answered yet
 * (kind "gobiker" / "resident"), then shows a banner, loops the alarm,
 * flashes the tab title, sends a desktop notification and updates the sidebar badge.
 * The alarm stops once each emergency is marked "Responding" or resolved, or is silenced here.
 */
(() => {
    const root = document.getElementById('emergency-alert');
    if (!root) return;

    const POLL_MS = 5000;
    const FAILURES_BEFORE_WARNING = 3;
    // Per-browser settings, shared by all admin tabs.
    const SOUND_KEY = 'gobike:alarm-sound';
    const SILENCED_KEY = 'gobike:alarm-silenced';
    const NOTIFIED_KEY = 'gobike:alarm-notified';

    const $ = (selector, el = root) => el?.querySelector(selector) ?? null;
    const els = {
        banner: $('[data-emer-banner]'),
        title: $('[data-emer-title]'),
        list: $('[data-emer-list]'),
        note: $('[data-emer-note]'),
        silence: $('[data-emer-silence]'),
        conn: $('[data-emer-conn]'),
        announce: $('[data-emer-announce]'),
        row: $('[data-emer-row]'),
        badge: document.querySelector('[data-nav-badge="emergencies"]'),
        sosBadge: document.querySelector('[data-nav-badge="sos"]'),
        controls: document.querySelector('[data-alarm-controls]'),
    };
    const controls = {
        toggle: $('[data-alarm-toggle]', els.controls),
        test: $('[data-alarm-test]', els.controls),
        notify: $('[data-alarm-notify]', els.controls),
    };

    // localStorage can be missing or throw (private windows, blocked site data): the alarm still works.
    const store = {
        get(key, fallback) {
            try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; }
        },
        set(key, value) {
            try { localStorage.setItem(key, JSON.stringify(value)); } catch { /* not remembered */ }
        },
    };

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const canNotify = () => 'Notification' in window && window.isSecureContext;
    const baseTitle = document.title;

    let emergencies = [];
    let shownKeys = '';
    let soundOn = store.get(SOUND_KEY, true) !== false;
    let silenced = new Set(store.get(SILENCED_KEY, []));
    const notified = new Set();
    let blocked = false; // the browser refused to play because nobody has clicked the page yet
    let primed = false;
    let testing = false;
    let testTimer = null;
    let flashTimer = null;
    let failures = 0;
    let stopped = false;
    let inFlight = false;
    let pollQueued = false;
    let ticker = null;
    let fallbackTimer = null;
    let noteMessage = null;
    let noteTimer = null;
    let lastOk = null; // when the server last answered, for the sidebar status
    let connState = null; // the warning currently shown in the connection bar, if any

    const audio = new Audio(root.dataset.sound);
    audio.loop = true;
    audio.preload = 'auto';

    const ringing = () => (stopped ? [] : emergencies.filter((e) => !silenced.has(e.key)));
    const mapHref = (id) => `${root.dataset.mapUrl}?focus=${encodeURIComponent(id)}`;
    const isResident = (e) => e.kind === 'resident';
    // A resident's SOS opens its request page; a GoBiker's emergency opens the live map.
    const openEmergency = (e) => {
        if (isResident(e)) window.location.href = e.view_url;
        else showOnMap(e.location_id);
    };

    const ago = (iso) => {
        if (!iso) return 'recently';
        const s = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
        if (s < 60) return 'just now';
        if (s < 3600) return `${Math.floor(s / 60)} min ago`;
        const h = Math.floor(s / 3600);
        return h < 24 ? `${h} h ago` : `${Math.floor(h / 24)} d ago`;
    };

    /* ---------- Sound ---------- */

    function syncSound() {
        if (testing) return;
        if (soundOn && ringing().length) {
            if (audio.paused) {
                audio.play()
                    .then(() => { blocked = false; renderNote(); publish(); })
                    .catch((err) => { blocked = err?.name === 'NotAllowedError'; renderNote(); publish(); });
            }
        } else if (!audio.paused) {
            audio.pause();
            audio.currentTime = 0;
        }
    }

    // Browsers only allow sound after the admin has clicked or typed on the page. A blocked alarm
    // starts on that click; otherwise the audio is started muted once so it may play later on its own (Safari).
    function onGesture() {
        if (soundOn && ringing().length) {
            syncSound();
            return;
        }
        if (primed || !audio.paused) return;
        primed = true;
        audio.muted = true;
        audio.play()
            .then(() => {
                if (!testing && !(soundOn && ringing().length)) {
                    audio.pause();
                    audio.currentTime = 0;
                }
                audio.muted = false;
            })
            .catch(() => {
                audio.muted = false;
                primed = false;
            });
    }

    function setSound(on) {
        soundOn = on;
        store.set(SOUND_KEY, on);
        renderControls();
        renderNote();
        syncSound();
        publish();
    }

    function silenceAll() {
        emergencies.forEach((e) => silenced.add(e.key));
        store.set(SILENCED_KEY, [...silenced]);
        renderNote();
        syncSound();
    }

    function testSound() {
        testing = true;
        clearTimeout(testTimer);
        controls.test.textContent = 'Playing…';
        audio.muted = false;
        audio.currentTime = 0;
        audio.play().catch(() => {
            controls.test.textContent = 'Sound blocked';
            controls.test.title = 'The browser would not play the alarm. Allow this site to play sound in the browser settings.';
        });
        testTimer = setTimeout(() => {
            testing = false;
            controls.test.textContent = 'Test';
            audio.pause();
            audio.currentTime = 0;
            syncSound();
        }, 3000);
    }

    /* ---------- Tab title, sidebar badge, desktop notifications ---------- */

    function syncTitle() {
        if (!emergencies.length) {
            clearInterval(flashTimer);
            flashTimer = null;
            document.title = baseTitle;
            return;
        }
        if (flashTimer) return;
        let on = true;
        const alertTitle = () => `🚨 EMERGENCY (${emergencies.length}) | OneGoBike Admin`;
        document.title = alertTitle();
        flashTimer = setInterval(() => {
            on = !on;
            document.title = on ? alertTitle() : baseTitle;
        }, 1000);
    }

    function syncBadge() {
        const setBadge = (badge, n) => {
            if (!badge) return;
            badge.hidden = n === 0;
            const count = $('.count-num', badge);
            if (count) count.textContent = new Intl.NumberFormat().format(n);
        };
        const residents = emergencies.filter(isResident).length;
        setBadge(els.badge, emergencies.length - residents);
        setBadge(els.sosBadge, residents);
    }

    function notify() {
        const already = new Set([...notified, ...store.get(NOTIFIED_KEY, [])]);
        const fresh = emergencies.filter((e) => !already.has(e.key));

        // Only open emergencies are remembered, shared with other admin tabs so each notifies once.
        notified.clear();
        emergencies.forEach((e) => notified.add(e.key));
        store.set(NOTIFIED_KEY, [...notified]);

        if (!canNotify() || Notification.permission !== 'granted') return;

        fresh.forEach((e) => {
            try {
                const n = new Notification(isResident(e) ? `SOS: ${e.name}` : `Emergency: ${e.name}`, {
                    body: isResident(e)
                        ? `${e.barangay || 'No location given'}. Open the request to respond.`
                        : `${e.barangay || 'No barangay assigned'}. Open the live map to respond.`,
                    tag: `gobike-emergency-${e.key}`,
                    requireInteraction: true,
                });
                n.onclick = () => {
                    window.focus();
                    openEmergency(e);
                    n.close();
                };
            } catch {
                // Some browsers (Android Chrome) only allow notifications from a service worker.
            }
        });
    }

    /* ---------- Banner ---------- */

    function buildRow(e) {
        const li = els.row.content.firstElementChild.cloneNode(true);
        li.dataset.key = e.key;
        $('[data-field="name"]', li).textContent = isResident(e) ? `SOS from ${e.name} (resident)` : e.name;
        $('[data-field="barangay"]', li).textContent = e.barangay || (isResident(e) ? 'No location given' : 'No barangay assigned');
        $('[data-field="ago"]', li).dateTime = e.since ?? '';

        const call = $('[data-field-href="call"]', li);
        if (e.mobile) {
            call.href = `tel:${e.mobile}`;
            call.setAttribute('aria-label', `Call ${e.name} at ${e.mobile}`);
            $('[data-field="mobile"]', li).textContent = e.mobile;
        } else {
            call.remove();
        }

        const map = $('[data-field-href="map"]', li);
        const respond = $('[data-emer-respond]', li);
        if (isResident(e)) {
            map.href = e.view_url;
            delete map.dataset.emerMap;
            $('[data-field="open-label"]', map).textContent = 'Open request';
            respond.dataset.respondUrl = e.respond_url;
        } else {
            map.href = mapHref(e.location_id);
            map.dataset.locationId = e.location_id;
            respond.dataset.respondUrl = `${root.dataset.locationsUrl}/${encodeURIComponent(e.location_id)}/status`;
        }
        respond.setAttribute('aria-label', `Mark ${e.name} as responding`);

        return li;
    }

    function render() {
        els.banner.hidden = emergencies.length === 0;
        if (!emergencies.length) {
            shownKeys = '';
            els.list.replaceChildren();
            return;
        }

        els.title.textContent = emergencies.length === 1
            ? `Emergency: ${emergencies[0].name} needs help`
            : `Emergency: ${emergencies.length} GoBikers need help`;

        // Rows are rebuilt only when the emergencies change, so focus and clicks are not lost.
        const keys = emergencies.map((e) => e.key).join('|');
        if (keys !== shownKeys) {
            shownKeys = keys;
            els.list.replaceChildren(...emergencies.map(buildRow));
        }
        els.list.querySelectorAll('time[data-field="ago"]').forEach((t) => { t.textContent = ago(t.dateTime); });

        renderNote();
    }

    function renderNote() {
        const anyRinging = ringing().length > 0;
        els.silence.hidden = !(soundOn && anyRinging && !blocked);

        let text = null;
        let action = null;
        if (noteMessage) {
            text = noteMessage;
        } else if (!emergencies.length) {
            text = null;
        } else if (!soundOn) {
            text = 'The alarm sound is off in this browser.';
            action = 'Turn on sound';
        } else if (blocked && anyRinging) {
            text = 'Your browser is holding back the alarm sound until you click on this page.';
            action = 'Play alarm';
        } else if (!anyRinging) {
            text = 'Alarm silenced. It sounds again if another emergency comes in.';
        }

        els.note.hidden = !text;
        if (!text) return;
        els.note.replaceChildren(text);
        if (action) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'emer-note-btn';
            button.dataset.emerSoundOn = '';
            button.textContent = action;
            els.note.append(' ', button);
        }
    }

    function flashNote(message) {
        noteMessage = message;
        renderNote();
        clearTimeout(noteTimer);
        noteTimer = setTimeout(() => {
            noteMessage = null;
            render();
        }, 6000);
    }

    function setConn(state) {
        const changed = state !== connState;
        const previous = connState;
        connState = state;
        els.conn.hidden = !state;
        if (!state) {
            // A live region that appears and fills in the same moment is often not read, so say it on the always-present one.
            if (changed && previous) els.announce.textContent = 'Emergency alerts are working again.';
            return;
        }
        els.conn.dataset.state = state;
        if (state === 'offline') {
            els.conn.textContent = 'Cannot reach the server, so emergency alerts may be late. Retrying…';
        } else {
            const link = document.createElement('a');
            link.href = root.dataset.loginUrl;
            link.textContent = 'Sign in again';
            els.conn.replaceChildren('You are signed out, so emergency alerts are off. ', link);
        }
        if (changed) els.announce.textContent = els.conn.textContent;
    }

    /* ---------- Status for the sidebar ---------- */

    // connecting -> live; one failed check -> reconnecting; three, or no network -> offline; session over -> signed-out
    function connectionState() {
        if (stopped) return 'signed-out';
        if (failures >= FAILURES_BEFORE_WARNING || navigator.onLine === false) return 'offline';
        if (failures > 0) return 'reconnecting';
        return lastOk ? 'live' : 'connecting';
    }

    // The state also lives on the element, because this file runs before sidebar.js has started listening.
    function publish() {
        const state = connectionState();
        root.dataset.state = state;
        root.dataset.lastOk = lastOk ?? '';
        document.dispatchEvent(new CustomEvent('gobike:alert-status', { detail: { state, lastOk, soundOn, blocked } }));
    }

    function renderControls() {
        if (!els.controls) return;
        els.controls.hidden = false;
        controls.toggle.setAttribute('aria-pressed', String(soundOn));
        $('[data-when-sound="on"]', controls.toggle).hidden = !soundOn;
        $('[data-when-sound="off"]', controls.toggle).hidden = soundOn;
        controls.notify.hidden = !canNotify() || Notification.permission !== 'default';
    }

    /* ---------- Data ---------- */

    function update(list) {
        const previous = new Set(emergencies.map((e) => e.key));
        emergencies = list;

        // Forget silenced alarms for emergencies that are over.
        const open = new Set(list.map((e) => e.key));
        const stillSilenced = [...silenced].filter((key) => open.has(key));
        if (stillSilenced.length !== silenced.size) {
            silenced = new Set(stillSilenced);
            store.set(SILENCED_KEY, stillSilenced);
        }

        const fresh = list.filter((e) => !previous.has(e.key));
        if (fresh.length) {
            els.announce.textContent = fresh.length === 1
                ? `Emergency: ${fresh[0].name} needs help.`
                : `${fresh.length} new emergencies from GoBikers.`;
        }

        render();
        notify();
        syncSound();
        syncTitle();
        syncBadge();
    }

    async function poll() {
        if (stopped) return;
        if (inFlight) {
            pollQueued = true;
            return;
        }
        inFlight = true;
        try {
            const res = await fetch(root.dataset.url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
                // A stalled request would otherwise leave the sidebar saying "live" with an old time.
                signal: typeof AbortSignal.timeout === 'function' ? AbortSignal.timeout(8000) : undefined,
            });
            if ([401, 403, 419].includes(res.status)) {
                signedOut();
                return;
            }
            if (!res.ok) throw new Error(String(res.status));

            const data = await res.json();
            failures = 0;
            lastOk = Date.now();
            setConn(null);
            update(Array.isArray(data.emergencies) ? data.emergencies : []);
        } catch {
            failures += 1;
            if (failures >= FAILURES_BEFORE_WARNING) setConn('offline');
        } finally {
            inFlight = false;
            publish();
            if (pollQueued) {
                pollQueued = false;
                poll();
            }
        }
    }

    function signedOut() {
        stopped = true;
        ticker?.terminate();
        clearInterval(fallbackTimer);
        setConn('signed-out');
        syncSound();
        publish();
    }

    function startTimer() {
        const fallback = () => {
            ticker?.terminate();
            ticker = null;
            clearInterval(fallbackTimer);
            fallbackTimer = setInterval(poll, POLL_MS);
        };
        try {
            ticker = new Worker(new URL('./emergency-ticker.js', import.meta.url));
            ticker.onmessage = poll;
            ticker.onerror = fallback;
            ticker.postMessage(POLL_MS);
        } catch {
            // e.g. while `npm run dev` serves scripts from another address
            fallback();
        }
    }

    async function respond(button) {
        button.disabled = true;
        try {
            const res = await fetch(button.dataset.respondUrl, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ status: 'responding' }),
            });
            // 422: another admin already answered or resolved it; the next poll shows that.
            if (!res.ok && res.status !== 422) throw new Error(String(res.status));
            document.dispatchEvent(new CustomEvent('gobike:emergencies-changed', { detail: { source: 'banner' } }));
            poll();
        } catch {
            button.disabled = false;
            flashNote('Could not mark this emergency as responding. Please try again.');
        }
    }

    function showOnMap(locationId) {
        // Already on the live map: move the map instead of reloading the page.
        if (document.getElementById('map')) {
            document.dispatchEvent(new CustomEvent('gobike:show-location', { detail: { locationId: Number(locationId) } }));
            return;
        }
        window.location.href = mapHref(locationId);
    }

    /* ---------- Events ---------- */

    document.addEventListener('pointerdown', onGesture, true);
    document.addEventListener('keydown', onGesture, true);

    root.addEventListener('click', (e) => {
        const respondButton = e.target.closest('[data-emer-respond]');
        if (respondButton) {
            respond(respondButton);
            return;
        }
        const mapLink = e.target.closest('[data-emer-map]');
        if (mapLink && document.getElementById('map')) {
            e.preventDefault();
            showOnMap(mapLink.dataset.locationId);
            return;
        }
        if (e.target.closest('[data-emer-silence]')) silenceAll();
        if (e.target.closest('[data-emer-sound-on]')) setSound(true);
    });

    controls.toggle?.addEventListener('click', () => setSound(!soundOn));
    controls.test?.addEventListener('click', testSound);
    controls.notify?.addEventListener('click', async () => {
        try { await Notification.requestPermission(); } catch { /* old browsers */ }
        renderControls();
    });

    // Settings changed in another admin tab.
    window.addEventListener('storage', (e) => {
        if (e.key === SOUND_KEY) {
            soundOn = store.get(SOUND_KEY, true) !== false;
            renderControls();
        } else if (e.key === SILENCED_KEY) {
            silenced = new Set(store.get(SILENCED_KEY, []));
        } else {
            return;
        }
        renderNote();
        syncSound();
    });

    document.addEventListener('gobike:emergencies-changed', (e) => {
        if (e.detail?.source !== 'banner') poll();
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    window.addEventListener('offline', () => {
        if (!stopped) setConn('offline');
        publish();
    });
    window.addEventListener('online', () => {
        publish();
        poll();
    });

    renderControls();
    publish();
    poll();
    startTimer();
})();
