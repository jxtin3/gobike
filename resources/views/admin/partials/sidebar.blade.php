@php
    $me = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($me->name ?? 'Admin')))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $current = fn (string ...$patterns) => request()->routeIs(...$patterns) ? 'page' : 'false';
@endphp
<aside class="adm-side" id="adm-side" data-side aria-label="Admin navigation">
    <a href="{{ route('admin.dashboard') }}" class="side-brand">
        <img src="{{ asset('images/gobike-logo.png') }}" alt="">
        <span><b>OneGoBike</b><small>Admin</small></span>
    </a>

    <nav class="side-nav">
        <a class="side-link" href="{{ route('admin.dashboard') }}" aria-current="{{ $current('admin.dashboard') }}">
            <x-admin.icon name="map" /><span>Live map</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations') }}" aria-current="{{ $current('admin.operations') }}">
            <x-admin.icon name="grid" /><span>Operations</span>
        </a>

        <p class="side-group">Content</p>
        <a class="side-link" href="{{ route('admin.operations.news.index') }}" aria-current="{{ $current('admin.operations.news.*') }}">
            <x-admin.icon name="file-text" /><span>News</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.pictures.index') }}" aria-current="{{ $current('admin.operations.pictures.*') }}">
            <x-admin.icon name="image" /><span>Pictures</span>
        </a>
        <a class="side-link" href="{{ route('admin.operations.messages.index') }}" aria-current="{{ $current('admin.operations.messages.*') }}">
            <x-admin.icon name="mail" /><span>Messages</span>
            @if (($navUnread ?? 0) > 0)
                <span class="badge badge-brand" data-unread-badge>{{ $navUnread }}</span>
            @endif
        </a>

        <p class="side-group">People</p>
        <a class="side-link" href="{{ route('admin.operations.users.index') }}" aria-current="{{ $current('admin.operations.users.*') }}">
            <x-admin.icon name="users" /><span>Users</span>
            @if (($navPending ?? 0) > 0)
                <span class="badge badge-warn" title="GoBiker sign-ups waiting for approval">{{ $navPending }}</span>
            @endif
        </a>

        <p class="side-group">Insights</p>
        <span class="side-link is-disabled" aria-disabled="true">
            <x-admin.icon name="bar-chart" /><span>Reports</span><span class="badge badge-soft">Soon</span>
        </span>
    </nav>

    <div class="side-foot">
        <div class="profile">
            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
            <div class="profile-meta">
                <b>{{ $me->name }}</b>
                <span>{{ $me->email }}</span>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="side-logout"><x-admin.icon name="log-out" /> Log out</button>
        </form>
    </div>
</aside>