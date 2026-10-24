@extends('admin.operations.layout')
@section('title', 'GoBiker messages')
@section('heading', 'GoBiker messages')
@section('subheading', 'Messages GoBikers send from the mobile app.')

@section('content')
@php
    $tabs = ['all' => 'All messages', 'unread' => 'Unread', 'read' => 'Read'];
    $currentTab = $statusFilter ?? 'all';
    $searching = filled(request('search'));
    $hasQuery = $searching || $statusFilter;
@endphp

<nav class="tabs" aria-label="Filter messages">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.gobiker-messages.index', array_filter(['status' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
           class="tab"
           aria-current="{{ $currentTab === $key ? 'page' : 'false' }}" data-skeleton="rows">
            {{ $label }}
            @if ($key === 'all')
                <span class="count">{{ number_format($totalCount) }}</span>
            @elseif ($key === 'unread')
                <span class="count" data-unread-count>{{ number_format($unreadCount) }}</span>
            @endif
        </a>
    @endforeach
</nav>

<x-admin.filters :action="route('admin.operations.gobiker-messages.index')" placeholder="Search by GoBiker name or message" label="Search GoBiker messages"
                 :hidden="['status' => $statusFilter]" :filtered="$searching" />

<div data-list>
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
                @if ($searching)
                    <x-admin.empty :row="5" icon="message-square" title="No messages match these filters" :clear="route('admin.operations.gobiker-messages.index')">
                        Try a different name or word, or clear the filters.
                    </x-admin.empty>
                @elseif ($statusFilter === 'unread')
                    <x-admin.empty :row="5" icon="check-circle" title="You're all caught up">
                        There are no unread GoBiker messages.
                    </x-admin.empty>
                @elseif ($statusFilter === 'read')
                    <x-admin.empty :row="5" icon="message-square" title="No read messages">
                        Messages you open will appear here.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="5" icon="message-square" title="No messages yet">
                        Messages GoBikers send from the mobile app will appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$messages" noun="message" />
</div>

<x-admin.dialog id="gobikerMessageDialog" kicker="GoBiker message" field="sender">
    <x-slot:sub><span data-pill-field="status"></span></x-slot:sub>

    <dl class="dlg-meta">
        <div><dt>Barangay</dt><dd data-field="barangay"></dd></div>
        <div><dt>Mobile</dt><dd data-field="mobile"></dd></div>
        <div><dt>Received</dt><dd data-field="received"></dd></div>
    </dl>

    <section>
        <h3 class="dlg-label">Message</h3>
        <p class="dlg-text" data-field="message"></p>
    </section>

    <x-slot:foot>
        <div class="dlg-foot-main">
            <button type="button" class="btn btn-secondary" data-dialog-close>Close</button>
        </div>
    </x-slot:foot>
</x-admin.dialog>
@endsection
