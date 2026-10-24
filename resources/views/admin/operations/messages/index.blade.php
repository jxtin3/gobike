@extends('admin.operations.layout')
@section('title', 'Contact messages')
@section('heading', 'Contact messages')
@section('subheading', 'Messages sent through the contact form on the website.')

@section('content')
@php
    $tabs = ['all' => 'All messages', 'unread' => 'Unread', 'read' => 'Read'];
    $currentTab = $statusFilter ?? 'all';
    $searching = filled(request('search'));
    $hasQuery = $searching || $statusFilter;
@endphp

<nav class="tabs" aria-label="Filter messages">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.messages.index', array_filter(['status' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
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

<x-admin.filters :action="route('admin.operations.messages.index')" placeholder="Search name, email or message" label="Search messages"
                 :hidden="['status' => $statusFilter]" :filtered="$searching" />

<div data-list>
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
                @if ($hasQuery)
                    <x-admin.empty :row="6" icon="inbox" title="No messages match these filters" :clear="route('admin.operations.messages.index')">
                        Try a different search, or clear the filters.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="6" icon="inbox" title="No messages yet">
                        Messages sent through the website contact form will appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$messages" noun="message" />
</div>

<x-admin.dialog id="messageDialog" kicker="Contact message" field="name">
    <x-slot:sub><span data-pill-field="status"></span></x-slot:sub>

    <dl class="dlg-meta">
        <div><dt>Email</dt><dd data-field="email"></dd></div>
        <div><dt>Phone</dt><dd data-field="phone"></dd></div>
        <div><dt>Topic</dt><dd data-field="topic"></dd></div>
        <div><dt>Received</dt><dd data-field="received"></dd></div>
        <div class="dlg-meta-wide" data-show-field="otherConcern"><dt>Their concern</dt><dd data-field="otherConcern"></dd></div>
    </dl>

    <section>
        <h3 class="dlg-label">Message</h3>
        <p class="dlg-text" data-field="message"></p>
    </section>

    <x-slot:foot>
        <form method="POST" class="dlg-foot-danger" data-action-field="deleteUrl" data-confirm-field="deleteText"
              data-confirm-title="Delete this message?" data-confirm-ok="Delete message">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Delete</button>
        </form>
        <div class="dlg-foot-main">
            <button type="button" class="btn btn-secondary" data-dialog-close>Close</button>
            <a class="btn btn-primary" data-href-field="mailto"><x-admin.icon name="mail" /> Reply by email</a>
        </div>
    </x-slot:foot>
</x-admin.dialog>
@endsection
