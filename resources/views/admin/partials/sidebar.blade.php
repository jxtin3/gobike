{{--
    Admin sidebar: four main items, then three groups that open and close. Whether the sidebar is collapsed to icons and
    which groups are closed is remembered in two cookies (written by resources/js/admin/sidebar.js, read in
    AppServiceProvider), so the page arrives already in the right shape.
    Tests pin: the badge markup (<x-admin.nav-badge>), the emergencies badge always being rendered, and data-alarm-*.
--}}
@php
    $me = auth()->user();
    $sideRail = $sideRail ?? false;
    $sideClosed = $sideClosed ?? [];

    $groups = [
        'people' => [
            'label' => 'People',
            'items' => [
                ['route' => 'admin.operations.users.index', 'match' => ['admin.operations.users.*'], 'icon' => 'users', 'label' => 'Users', 'skeleton' => 'table',
                    'badge' => ['count' => $navPending ?? 0, 'tone' => 'warn', 'label' => 'waiting for approval', 'name' => 'users']],
                ['route' => 'admin.operations.volunteers.index', 'match' => ['admin.operations.volunteers.*'], 'icon' => 'user-plus', 'label' => 'Volunteers', 'skeleton' => 'table',
                    'badge' => ['count' => $navVolunteersPending ?? 0, 'tone' => 'warn', 'label' => 'pending review', 'name' => 'volunteers']],
                ['route' => 'admin.operations.patients.index', 'match' => ['admin.operations.patients.*'], 'icon' => 'activity', 'label' => 'Patients', 'skeleton' => 'table'],
                ['route' => 'admin.operations.appointments.index', 'match' => ['admin.operations.appointments.*'], 'icon' => 'clock', 'label' => 'Appointments', 'skeleton' => 'table',
                    'badge' => ['count' => $navAppointmentsPending ?? 0, 'tone' => 'warn', 'label' => 'waiting for a GoBiker', 'name' => 'appointments']],
                ['route' => 'admin.operations.gobiker-reports.index', 'match' => ['admin.operations.gobiker-reports.*'], 'icon' => 'trending-up', 'label' => 'GoBiker reports', 'skeleton' => 'table'],
                ['route' => 'admin.operations.feedback.index', 'match' => ['admin.operations.feedback.*'], 'icon' => 'star', 'label' => 'Feedback', 'skeleton' => 'table'],
            ],
        ],
        'requests' => [
            'label' => 'Requests & donations',
            'items' => [
                ['route' => 'admin.operations.training-requests.index', 'match' => ['admin.operations.training-requests.*'], 'icon' => 'clipboard', 'label' => 'Training requests', 'skeleton' => 'table',
                    'badge' => ['count' => $navTrainingPending ?? 0, 'tone' => 'warn', 'label' => 'pending', 'name' => 'training']],
                ['route' => 'admin.operations.chapter-inquiries.index', 'match' => ['admin.operations.chapter-inquiries.*'], 'icon' => 'navigation', 'label' => 'Chapter inquiries', 'skeleton' => 'table',
                    'badge' => ['count' => $navChaptersPending ?? 0, 'tone' => 'warn', 'label' => 'pending', 'name' => 'chapters']],
                ['route' => 'admin.operations.donations.index', 'match' => ['admin.operations.donations.*'], 'icon' => 'dollar-sign', 'label' => 'Donations', 'skeleton' => 'table'],
            ],
        ],
        'content' => [
            'label' => 'Content',
            'items' => [
                ['route' => 'admin.operations.announcements.index', 'match' => ['admin.operations.announcements.*'], 'icon' => 'megaphone', 'label' => 'Announcements', 'skeleton' => 'table'],
                ['route' => 'admin.operations.news.index', 'match' => ['admin.operations.news.*'], 'icon' => 'file-text', 'label' => 'News', 'skeleton' => 'table'],
                ['route' => 'admin.operations.pictures.index', 'match' => ['admin.operations.pictures.*'], 'icon' => 'image', 'label' => 'Pictures', 'skeleton' => 'grid'],
                ['route' => 'admin.operations.homepage-data.index', 'match' => ['admin.operations.homepage-data.*'], 'icon' => 'edit', 'label' => 'Homepage content', 'skeleton' => 'form'],
                ['route' => 'admin.operations.legal.terms', 'match' => ['admin.operations.legal.*'], 'icon' => 'shield', 'label' => 'Legal pages', 'skeleton' => 'document'],
            ],
        ],
    ];
@endphp
<aside class="adm-side" id="adm-side" data-side aria-label="Admin navigation">
    <div class="side-head">
        <a href="{{ route('admin.dashboard') }}" class="side-brand">
            <img src="{{ asset('images/gobike-logo-transparent.png') }}" alt="" width="662" height="362">
            <span class="side-brand-text"><b>Go Bike</b><small>Admin</small></span>
        </a>
        {{-- Desktop: collapse to icons. Phones: this slot closes the drawer instead. --}}
        <button type="button" class="icon-btn side-toggle" data-side-toggle aria-controls="adm-side"
                aria-label="{{ $sideRail ? 'Expand sidebar' : 'Collapse sidebar' }}"
                data-tip="{{ $sideRail ? 'Expand sidebar' : 'Collapse sidebar' }}">
            <x-admin.icon name="panel-left" />
        </button>
        <button type="button" class="icon-btn side-close" data-side-close aria-label="Close menu"><x-admin.icon name="x" /></button>
    </div>

    <nav class="side-nav" aria-label="Admin">
        <ul class="side-list side-main">
            <li>
                <x-admin.side-link :href="route('admin.dashboard')" icon="bar-chart" label="Dashboard" :match="['admin.dashboard']" skeleton="dashboard" />
            </li>
            <li>
                <x-admin.side-link :href="route('admin.live-map.index')" icon="map" label="Live map" :match="['admin.live-map.*']" skeleton="map">
                    {{-- Always rendered so resources/js/admin/emergency-alert.js can show it as emergencies come in. --}}
                    <span class="badge badge-bad" data-nav-badge="emergencies"@if (! ($navEmergencies ?? 0)) hidden @endif><span class="count-num">{{ number_format($navEmergencies ?? 0) }}</span><span class="sr-only"> emergencies waiting for a response</span></span>
                </x-admin.side-link>
            </li>
            <li>
                <x-admin.side-link :href="route('admin.operations.sos.index')" icon="alert-triangle" label="Resident SOS" :match="['admin.operations.sos.*']" skeleton="rows">
                    {{-- Always rendered: resources/js/admin/emergency-alert.js updates it as requests come in. --}}
                    <span class="badge badge-bad" data-nav-badge="sos"@if (! ($navSos ?? 0)) hidden @endif><span class="count-num">{{ number_format($navSos ?? 0) }}</span><span class="sr-only"> SOS requests waiting for a response</span></span>
                </x-admin.side-link>
            </li>
            <li>
                <x-admin.side-link :href="route('admin.operations.messages.index')" icon="mail" label="Messages" :match="['admin.operations.messages.*']">
                    <x-admin.nav-badge :count="$navUnread ?? 0" tone="brand" label="unread" name="messages" />
                </x-admin.side-link>
            </li>
            <li>
                <x-admin.side-link :href="route('admin.operations.gobiker-messages.index')" icon="message-square" label="GoBiker messages" :match="['admin.operations.gobiker-messages.*']">
                    <x-admin.nav-badge :count="$navGobikerUnread ?? 0" tone="brand" label="unread" name="gobiker-messages" />
                </x-admin.side-link>
            </li>
        </ul>

        @foreach ($groups as $key => $group)
            @php
                $here = collect($group['items'])->contains(fn ($item) => request()->routeIs(...$item['match']));
                $open = $here || ! in_array($key, $sideClosed, true);
                $waiting = collect($group['items'])->sum(fn ($item) => $item['badge']['count'] ?? 0);
            @endphp
            <div class="side-section" data-group="{{ $key }}">
                <button type="button" class="side-group-toggle" data-group-toggle="{{ $key }}"
                        aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="side-items-{{ $key }}">
                    <span class="side-group-label">{{ $group['label'] }}</span>
                    {{-- Shown only while the group is closed, so waiting items are never hidden from view. --}}
                    @if ($waiting > 0)
                        <span class="badge badge-warn side-group-sum" data-group-badge="{{ $key }}"><span class="count-num">{{ number_format($waiting) }}</span><span class="sr-only"> waiting</span></span>
                    @endif
                    <x-admin.icon name="chevron-down" class="side-chevron" />
                </button>
                <div class="side-group-items" id="side-items-{{ $key }}">
                    <ul class="side-list">
                        @foreach ($group['items'] as $item)
                            <li>
                                <x-admin.side-link :href="route($item['route'])" :icon="$item['icon']" :label="$item['label']" :match="$item['match']" :skeleton="$item['skeleton']">
                                    @isset($item['badge'])
                                        <x-admin.nav-badge :count="$item['badge']['count']" :tone="$item['badge']['tone']" :label="$item['badge']['label']" :name="$item['badge']['name']" />
                                    @endisset
                                </x-admin.side-link>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </nav>

    <div class="side-foot">
        {{--
            Emergency-alert status. Hidden until resources/js/admin/emergency-alert.js is running; sidebar.js then
            keeps it current. The panel holds this browser's alarm settings (they used to sit loose in the footer).
        --}}
        <div class="side-status" data-side-status data-state="connecting" hidden>
            <button type="button" class="side-status-btn" popovertarget="side-alarm" data-tip="Emergency alerts">
                <span class="ss-dot" aria-hidden="true"></span>
                <span class="ss-copy">
                    <span class="ss-text" data-ss-text>Connecting…</span>
                    <span class="ss-meta" data-ss-meta>Emergency alerts</span>
                </span>
            </button>
            <div class="side-pop" id="side-alarm" popover>
                <p class="side-pop-title">Emergency alerts</p>
                <p class="side-pop-line" data-ss-detail>Checking for emergencies…</p>
                {{-- Alarm settings for this browser. Shown by emergency-alert.js (no script, no alarm). --}}
                <div class="side-alarm" data-alarm-controls hidden>
                    <button type="button" class="side-alarm-toggle" data-alarm-toggle aria-pressed="true">
                        <span data-when-sound="on"><x-admin.icon name="bell" /> Alarm sound on</span>
                        <span data-when-sound="off" hidden><x-admin.icon name="bell-off" /> Alarm sound off</span>
                    </button>
                    <button type="button" class="side-alarm-link" data-alarm-test>Test</button>
                    <button type="button" class="side-alarm-link side-alarm-notify" data-alarm-notify hidden>Turn on desktop alerts</button>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.profile.show') }}" class="profile" aria-current="{{ request()->routeIs('admin.profile.*') ? 'page' : 'false' }}" data-tip="{{ $me->name }}" data-skeleton="detail">
            <x-admin.avatar :name="$me->name" />
            <div class="profile-meta">
                <b>{{ $me->name }}</b>
                <span>{{ $me->email }}</span>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="side-logout" data-tip="Log out"><x-admin.icon name="log-out" /><span class="side-label">Log out</span></button>
        </form>
    </div>
</aside>
