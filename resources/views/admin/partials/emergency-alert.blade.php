{{--
    Emergency alerts on every admin page. resources/js/admin/emergency-alert.js polls
    admin.emergencies.index, fills this in, loops the alarm and flashes the tab title until
    each emergency is marked "Responding" or resolved.
--}}
<div class="emer-wrap" id="emergency-alert"
     data-url="{{ route('admin.emergencies.index') }}"
     data-locations-url="{{ route('admin.locations.index') }}"
     data-map-url="{{ route('admin.live-map.index') }}"
     data-login-url="{{ route('login') }}"
     data-sound="{{ asset('sounds/emergency-alarm.mp3') }}">

    <section class="emer-banner" aria-labelledby="emer-title" data-emer-banner hidden>
        <header class="emer-head">
            <span class="emer-icon"><x-admin.icon name="alert-triangle" /></span>
            <h2 class="emer-title" id="emer-title" data-emer-title>Emergency</h2>
            <button type="button" class="btn btn-sm emer-silence" data-emer-silence hidden>
                <x-admin.icon name="bell-off" /> Silence alarm
            </button>
        </header>

        <ul class="emer-list" data-emer-list></ul>

        <p class="emer-note" data-emer-note hidden></p>
    </section>

    {{-- Problems that stop alerts from arriving: shown even when there is no emergency. --}}
    <p class="emer-conn" data-emer-conn role="status" hidden></p>

    {{-- Read out once per new emergency (the list above refreshes every few seconds). --}}
    <p class="sr-only" role="alert" data-emer-announce></p>

    <template data-emer-row>
        <li class="emer-row">
            <div class="emer-who">
                <b data-field="name"></b>
                <small><span data-field="barangay"></span> · sent <time data-field="ago"></time></small>
            </div>
            <div class="emer-actions">
                <a class="btn btn-sm emer-btn" data-field-href="call"><x-admin.icon name="phone-call" /> <span data-field="mobile"></span></a>
                <a class="btn btn-sm emer-btn" data-field-href="map" data-emer-map><x-admin.icon name="map" /> Show on map</a>
                <button type="button" class="btn btn-sm emer-respond" data-emer-respond>Responding</button>
            </div>
        </li>
    </template>
</div>
