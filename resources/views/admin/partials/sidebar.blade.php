@php
    $me = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($me->name ?? 'Admin')))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $current = fn (string ...$patterns) => request()->routeIs(...$patterns) ? 'page' : 'false';
@endphp
<aside class="adm-side" id="adm-side" data-side aria-label="Admin navigation">
    <a href="{{ route('admin.dashboard') }}" class="side-brand">
        <img src="{{ asset('images/gobike-logo-transparent.png') }}" alt="">
        <span><b>Go Bike</b><small>Admin</small></span>
    </a>

    <nav class="side-nav">
        <a class="side-link" href="{{ route('admin.dashboard') }}" aria-current="{{ $current('admin.dashboard') }}">
            <x-admin.icon name="bar-chart" /><span>Dashboard</span>
        </a>
        <a class="side-link" href="{{ route('admin.live-map.index') }}" aria-current="{{ $current('admin.live-map.*') }}">
            <x-admin.icon name="map" /><span>Live map</span>
        </a>
        <p class="side-group">Content</p>
        <a class="side-link" href="{{ route('admin.operations.announcements.index') }}" aria-current="{{ $current('admin.operations.announcements.*') }}">
            <x-admin.icon name="megaphone" /><span>Announcements</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.news.index') }}" aria-current="{{ $current('admin.operations.news.*') }}">
            <x-admin.icon name="file-text" /><span>News</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.pictures.index') }}" aria-current="{{ $current('admin.operations.pictures.*') }}">
            <x-admin.icon name="image" /><span>Pictures</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.homepage-data.index') }}" aria-current="{{ $current('admin.operations.homepage-data.*') }}">
            <x-admin.icon name="edit" /><span>Homepage content</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.messages.index') }}" aria-current="{{ $current('admin.operations.messages.*') }}">
            <x-admin.icon name="mail" /><span>Messages</span>
            <x-admin.nav-badge :count="$navUnread ?? 0" tone="brand" label="unread" name="messages" />
        </a>
        <a class="side-link" href="{{ route('admin.operations.gobiker-messages.index') }}" aria-current="{{ $current('admin.operations.gobiker-messages.*') }}">
            <x-admin.icon name="message-square" /><span>GoBiker messages</span>
            <x-admin.nav-badge :count="$navGobikerUnread ?? 0" tone="brand" label="unread" name="gobiker-messages" />
        </a>

        <p class="side-group">People</p>
        <a class="side-link" href="{{ route('admin.operations.users.index') }}" aria-current="{{ $current('admin.operations.users.*') }}">
            <x-admin.icon name="users" /><span>Users</span>
            <x-admin.nav-badge :count="$navPending ?? 0" tone="warn" label="waiting for approval" name="users" />
        </a>
        <a class="side-link" href="{{ route('admin.operations.gobiker-reports.index') }}" aria-current="{{ $current('admin.operations.gobiker-reports.*') }}">
            <x-admin.icon name="trending-up" /><span>GoBiker reports</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.patients.index') }}" aria-current="{{ $current('admin.operations.patients.*') }}">
            <x-admin.icon name="activity" /><span>Patients</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.volunteers.index') }}" aria-current="{{ $current('admin.operations.volunteers.*') }}">
            <x-admin.icon name="user-plus" /><span>Volunteers</span>
            <x-admin.nav-badge :count="$navVolunteersPending ?? 0" tone="warn" label="pending review" name="volunteers" />
        </a>

        <p class="side-group">Requests &amp; donations</p>
        <a class="side-link" href="{{ route('admin.operations.donations.index') }}" aria-current="{{ $current('admin.operations.donations.*') }}">
            <x-admin.icon name="dollar-sign" /><span>Donations</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.training-requests.index') }}" aria-current="{{ $current('admin.operations.training-requests.*') }}">
            <x-admin.icon name="clipboard" /><span>Training requests</span>
            <x-admin.nav-badge :count="$navTrainingPending ?? 0" tone="warn" label="pending" name="training" />
        </a>
        <a class="side-link" href="{{ route('admin.operations.chapter-inquiries.index') }}" aria-current="{{ $current('admin.operations.chapter-inquiries.*') }}">
            <x-admin.icon name="navigation" /><span>Chapter inquiries</span>
            <x-admin.nav-badge :count="$navChaptersPending ?? 0" tone="warn" label="pending" name="chapters" />
        </a>

        <p class="side-group">Legal</p>
        <a class="side-link" href="{{ route('admin.operations.legal.terms') }}" aria-current="{{ $current('admin.operations.legal.*') }}">
            <x-admin.icon name="shield" /><span>Legal pages</span>
        </a>
    </nav>

    <div class="side-foot">
        <a href="{{ route('admin.profile.show') }}" class="profile" aria-current="{{ $current('admin.profile.*') }}">
            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
            <div class="profile-meta">
                <b>{{ $me->name }}</b>
                <span>{{ $me->email }}</span>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="side-logout"><x-admin.icon name="log-out" /> Log out</button>
        </form>
    </div>
</aside>