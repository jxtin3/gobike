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
                    data-read-url="{{ route('admin.operations.gobiker-messages.read', $msg) }}"
                    data-reply-url="{{ route('admin.operations.gobiker-messages.reply', $msg) }}"
                    data-reply="{{ $msg->reply_message }}"
                    data-replied-at="{{ $msg->replied_at?->format('M j, Y g:i A') }}"
                    data-replied-by="{{ $msg->adminReply?->name }}">
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

    <section data-show-field="reply">
        <h3 class="dlg-label">Your reply (sent to their app)</h3>
        <p class="dlg-text" data-field="reply"></p>
        <small class="sub-line" data-field="repliedAt"></small>
    </section>

    <form method="POST" class="reply-form" id="gobikerReplyForm" data-action-field="replyUrl" style="margin-top: 1rem; border-top: 1px solid var(--line); padding-top: 1rem;">
        @csrf
        <div class="field">
            <label for="reply_message" style="font-weight: 600; font-size: 0.875rem;">Reply to GoBiker</label>
            <textarea class="input" id="reply_message" name="reply_message" rows="3" maxlength="1000" required placeholder="Type your reply to send to their mobile app…"></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem;">
            <button type="submit" class="btn btn-ok btn-sm"><x-admin.icon name="mail" /> Send Reply</button>
        </div>
    </form>

    <x-slot:foot>
        <div class="dlg-foot-main">
            <button type="button" class="btn btn-secondary" data-dialog-close>Close</button>
        </div>
    </x-slot:foot>

    <script>
    // Handle Send Reply submit via XHR (same pattern as other admin dialogs)
    const replyForm = document.getElementById('gobikerReplyForm');
    if (replyForm) {
        const replyBtn = replyForm.querySelector('button[type="submit"]');
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (replyBtn.disabled) return;
            replyBtn.disabled = true;
            const formData = new FormData(replyForm);
            // admin.js sets replyForm.action from the row's data-reply-url via data-action-field="replyUrl"
            fetch(replyForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                credentials: 'same-origin'
            })
            .then(r => r.json().then(d => ({ ok: r.ok, data: d }), () => ({ ok: r.ok, data: null })))
            .then(({ ok, data }) => {
                if (ok && data?.success) {
                    document.getElementById('gobikerMessageDialog').close();
                    if (window.adminToast) window.adminToast({ text: 'Reply sent to GoBiker!' });
                    window.location.reload();
                } else {
                    replyBtn.disabled = false;
                    if (window.adminToast) window.adminToast({ text: data?.message || 'Failed to send reply. Please try again.', tone: 'error' });
                }
            })
            .catch(() => {
                replyBtn.disabled = false;
                if (window.adminToast) window.adminToast({ text: 'Failed to send reply. Please try again.', tone: 'error' });
            });
        });
    }
    </script>
</x-admin.dialog>
@endsection
