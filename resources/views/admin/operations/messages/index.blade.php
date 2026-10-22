@extends('admin.operations.layout')
@section('title', 'Messages')
@section('heading', 'Contact messages')
@section('subheading', 'Messages sent through the contact form on the website.')

@section('content')
@php
    $tabs = ['all' => 'All messages', 'unread' => 'Unread', 'read' => 'Read'];
    $currentTab = $statusFilter ?? 'all';
    $hasQuery = filled(request('search')) || $statusFilter;
@endphp

<nav class="tabs" aria-label="Filter messages">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.messages.index', array_filter(['status' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
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
    <form class="search" role="search" method="GET" action="{{ route('admin.operations.messages.index') }}">
        @if ($statusFilter) <input type="hidden" name="status" value="{{ $statusFilter }}"> @endif
        <x-admin.icon name="search" />
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email or message" aria-label="Search messages">
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>From</th>
                <th>Topic</th>
                <th>Message</th>
                <th>Received</th>
                <th>Status</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($messages as $item)
                @php
                    $isOther = in_array(strtolower($item->subject ?? ''), ['others', 'other'], true);
                    $topic = $isOther ? 'Others' : ucfirst($item->subject ?: '—');
                @endphp
                <tr @class(['is-clickable', 'is-unread' => ! $item->is_read])
                    data-dialog="#messageDialog"
                    data-name="{{ $item->name }}"
                    data-email="{{ $item->email }}"
                    data-phone="{{ $item->phone }}"
                    data-topic="{{ $topic }}"
                    data-other-concern="{{ $isOther ? $item->other_concern : '' }}"
                    data-received="{{ $item->created_at->format('M j, Y g:i A') }}"
                    data-status="{{ $item->is_read ? 'Read' : 'Unread' }}"
                    data-status-pill="{{ $item->is_read ? 'neutral' : 'brand' }}"
                    data-is-read="{{ $item->is_read ? '1' : '0' }}"
                    data-badge="messages"
                    data-message="{{ $item->message }}"
                    data-mailto="mailto:{{ $item->email }}?subject={{ rawurlencode('Re: '.($isOther && $item->other_concern ? $item->other_concern : $topic)) }}"
                    data-read-url="{{ route('admin.operations.messages.read', $item) }}"
                    data-delete-url="{{ route('admin.operations.messages.destroy', $item) }}"
                    data-delete-text="The message from {{ $item->name }} will be permanently deleted.">
                    <td>
                        <b>{{ $item->name }}</b>
                        <small class="sub-line">{{ $item->email }}</small>
                    </td>
                    <td><span class="pill pill-neutral">{{ $topic }}</span></td>
                    <td class="cell-preview">{{ $isOther && $item->other_concern ? $item->other_concern.' — ' : '' }}{{ $item->message }}</td>
                    <td>
                        {{ $item->created_at->format('M j, Y') }}
                        <small class="sub-line">{{ $item->created_at->format('g:i A') }}</small>
                    </td>
                    <td><span class="pill {{ $item->is_read ? 'pill-neutral' : 'pill-brand' }}" data-read-pill>{{ $item->is_read ? 'Read' : 'Unread' }}</span></td>
                    <td>
                        <div class="row-actions">
                            <button type="button" class="icon-btn" data-open title="Open" aria-label="Open message from {{ $item->name }}"><x-admin.icon name="eye" /></button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="is-static">
                    <td colspan="6">
                        <div class="empty">
                            <x-admin.icon name="inbox" />
                            @if ($hasQuery)
                                <b>No messages match</b>
                                <p>Try a different search, or clear the filters.</p>
                                <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.messages.index') }}">Clear filters</a>
                            @else
                                <b>No messages yet</b>
                                <p>Messages sent through the website contact form will appear here.</p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $messages->links('admin.partials.pagination') }}

<dialog id="messageDialog" class="msg-modal" aria-labelledby="message-dialog-title">
    <div class="msg-inner">
        <header class="msg-head">
            <div>
                <span class="msg-kicker">Contact message</span>
                <h2 id="message-dialog-title" data-field="name"></h2>
                <span data-pill-field="status"></span>
            </div>
            <button type="button" class="icon-btn" data-dialog-close aria-label="Close"><x-admin.icon name="x" /></button>
        </header>

        <dl class="msg-meta">
            <div><dt>Email</dt><dd data-field="email"></dd></div>
            <div><dt>Phone</dt><dd data-field="phone"></dd></div>
            <div><dt>Topic</dt><dd data-field="topic"></dd></div>
            <div><dt>Received</dt><dd data-field="received"></dd></div>
            <div class="msg-meta-wide" data-show-field="otherConcern"><dt>Their concern</dt><dd data-field="otherConcern"></dd></div>
        </dl>

        <section class="msg-body-wrapper">
            <h3 class="msg-body-label">Message</h3>
            <p class="msg-body" data-field="message"></p>
        </section>
        <p class="alert error" data-dialog-error role="alert" hidden></p>

        <footer class="msg-foot">
            <form method="POST" data-action-field="deleteUrl" data-confirm-field="deleteText"
                  data-confirm-title="Delete this message?" data-confirm-ok="Delete message">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Delete</button>
            </form>
            <a class="btn btn-primary" data-href-field="mailto"><x-admin.icon name="mail" /> Reply by email</a>
        </footer>
    </div>
</dialog>
@endsection
