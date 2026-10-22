@extends('admin.operations.layout')
@section('title', 'GoBiker messages')
@section('heading', 'GoBiker messages')
@section('subheading', 'Messages GoBikers send from the mobile app.')

@section('content')
@php
    $tabs = ['all' => 'All messages', 'unread' => 'Unread', 'read' => 'Read'];
    $currentTab = $statusFilter ?? 'all';
    $hasQuery = filled(request('search')) || $statusFilter;
@endphp

<nav class="tabs" aria-label="Filter messages">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.gobiker-messages.index', array_filter(['status' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
           class="tab"
           aria-current="{{ $currentTab === $key ? 'page' : 'false' }}">
            {{ $label }}
            @if ($key === 'all')
                <span class="count">{{ number_format($totalCount) }}</span>
            @elseif ($key === 'unread')
                <span class="count" data-unread-count>{{ number_format($unreadCount) }}</span>
            @endif
        </a>
    @endforeach
</nav>

<div class="toolbar">
    <form class="search" role="search" method="GET" action="{{ route('admin.operations.gobiker-messages.index') }}">
        @if ($statusFilter) <input type="hidden" name="status" value="{{ $statusFilter }}"> @endif
        <x-admin.icon name="search" />
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search by GoBiker name or message" aria-label="Search GoBiker messages">
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>GoBiker</th>
                <th>Message</th>
                <th>Received</th>
                <th>Status</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($messages as $msg)
                @php $sender = $msg->sender?->name ?? 'Deleted GoBiker'; @endphp
                <tr @class(['is-clickable', 'is-unread' => ! $msg->is_read])
                    data-dialog="#gobikerMessageDialog"
                    data-sender="{{ $sender }}"
                    data-barangay="{{ $msg->sender?->barangay }}"
                    data-mobile="{{ $msg->sender?->mobile }}"
                    data-message="{{ $msg->message }}"
                    data-received="{{ $msg->created_at->format('M j, Y g:i A') }}"
                    data-status="{{ $msg->is_read ? 'Read' : 'Unread' }}"
                    data-status-pill="{{ $msg->is_read ? 'neutral' : 'brand' }}"
                    data-is-read="{{ $msg->is_read ? '1' : '0' }}"
                    data-badge="gobiker-messages"
                    data-read-url="{{ route('admin.operations.gobiker-messages.read', $msg) }}">
                    <td>
                        <b>{{ $sender }}</b>
                        <small class="sub-line">{{ $msg->sender?->barangay ?: 'No barangay' }}</small>
                    </td>
                    <td class="cell-preview">{{ $msg->message }}</td>
                    <td>
                        {{ $msg->created_at->format('M j, Y') }}
                        <small class="sub-line">{{ $msg->created_at->format('g:i A') }}</small>
                    </td>
                    <td><span class="pill {{ $msg->is_read ? 'pill-neutral' : 'pill-brand' }}" data-read-pill>{{ $msg->is_read ? 'Read' : 'Unread' }}</span></td>
                    <td>
                        <div class="row-actions">
                            <button type="button" class="icon-btn" data-open title="Open" aria-label="Open message from {{ $sender }}"><x-admin.icon name="eye" /></button>
                            <form method="POST" action="{{ route('admin.operations.gobiker-messages.destroy', $msg) }}"
                                  data-confirm-title="Delete this message?"
                                  data-confirm-text="The message from {{ $sender }} will be permanently deleted."
                                  data-confirm-ok="Delete message">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete message from {{ $sender }}"><x-admin.icon name="trash" /></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="is-static">
                    <td colspan="5">
                        <div class="empty">
                            <x-admin.icon name="message-square" />
                            @if (filled(request('search')))
                                <b>No messages match</b>
                                <p>Try a different name or word, or clear the filters.</p>
                                <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.gobiker-messages.index') }}">Clear filters</a>
                            @elseif ($statusFilter === 'unread')
                                <b>You're all caught up</b>
                                <p>There are no unread GoBiker messages.</p>
                            @elseif ($hasQuery)
                                <b>No read messages</b>
                                <p>Messages you open will appear here.</p>
                            @else
                                <b>No messages yet</b>
                                <p>Messages GoBikers send from the mobile app will appear here.</p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $messages->links('admin.partials.pagination') }}

<dialog id="gobikerMessageDialog" class="msg-modal" aria-labelledby="gobiker-dialog-title">
    <div class="msg-inner">
        <header class="msg-head">
            <div>
                <span class="msg-kicker">GoBiker message</span>
                <h2 id="gobiker-dialog-title" data-field="sender"></h2>
                <span data-pill-field="status"></span>
            </div>
            <button type="button" class="icon-btn" data-dialog-close aria-label="Close"><x-admin.icon name="x" /></button>
        </header>

        <dl class="msg-meta">
            <div><dt>Barangay</dt><dd data-field="barangay"></dd></div>
            <div><dt>Mobile</dt><dd data-field="mobile"></dd></div>
            <div><dt>Received</dt><dd data-field="received"></dd></div>
        </dl>

        <section class="msg-body-wrapper">
            <h3 class="msg-body-label">Message</h3>
            <p class="msg-body" data-field="message"></p>
        </section>
        <p class="alert error" data-dialog-error role="alert" hidden></p>

        <footer class="msg-foot">
            <span></span>
            <button type="button" class="btn btn-secondary" data-dialog-close>Close</button>
        </footer>
    </div>
</dialog>
@endsection
