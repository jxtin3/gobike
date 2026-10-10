@extends('admin.operations.layout')

@section('title', 'GoBiker Messages')

@section('content')
<div class="ops-header gobiker-messages-page">
    <div>
        <h1 class="ops-title">GoBiker Messages</h1>
        <p class="ops-subtitle">Review messages sent by GoBikers through the mobile app.</p>
    </div>
</div>

<section class="gobiker-messages-page" aria-label="GoBiker message inbox">
    <div class="gobiker-message-overview">
        @php
            $readFilterUrl = route('admin.operations.gobiker-messages.index', array_merge(
                request()->except('page'),
                ['status' => $statusFilter === 'read' ? null : 'read']
            ));
            $unreadFilterUrl = route('admin.operations.gobiker-messages.index', array_merge(
                request()->except('page'),
                ['status' => $statusFilter === 'unread' ? null : 'unread']
            ));
        @endphp
        <a
            href="{{ $readFilterUrl }}"
            class="gobiker-message-stat {{ $statusFilter === 'read' ? 'is-active' : '' }}"
            @if($statusFilter === 'read') aria-current="page" @endif
        >
            <span class="gobiker-message-stat-label">All messages</span>
            <strong>{{ number_format($readMessages) }}</strong>
            <span class="gobiker-message-stat-note">Read messages · click to filter</span>
        </a>
        <a
            href="{{ $unreadFilterUrl }}"
            class="gobiker-message-stat gobiker-message-stat-unread {{ $statusFilter === 'unread' ? 'is-active' : '' }}"
            @if($statusFilter === 'unread') aria-current="page" @endif
        >
            <span class="gobiker-message-stat-label">Unread</span>
            <strong data-unread-count>{{ number_format($unreadMessages) }}</strong>
            <span class="gobiker-message-stat-note">Waiting for review · click to filter</span>
        </a>
    </div>

    <form method="GET" action="{{ route('admin.operations.gobiker-messages.index') }}" class="gobiker-search" role="search">
        <label class="gobiker-search-field">
            <x-admin.icon name="search" />
            <span class="sr-only">Search GoBiker messages</span>
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by GoBiker name or message"
                aria-label="Search GoBiker messages by name or message"
            >
            @if($statusFilter)
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
        </label>
        <button type="submit" class="gobiker-action gobiker-action-search">Search messages</button>
        @if(request('search'))
            <a href="{{ route('admin.operations.gobiker-messages.index', $statusFilter ? ['status' => $statusFilter] : []) }}" class="gobiker-action gobiker-action-clear">Clear search</a>
        @endif
    </form>

    @if(session('success'))
        <div class="alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="ops-table-wrap gobiker-message-table-wrap">
        <table class="ops-table gobiker-message-table">
            <thead>
                <tr>
                    <th>GoBiker</th>
                    <th>Barangay</th>
                    <th>Message preview</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                <tr class="{{ $msg->is_read ? '' : 'gobiker-message-unread' }}" id="msg-{{ $msg->id }}"
                    data-sender="{{ $msg->sender?->name ?? 'Unknown GoBiker' }}"
                    data-barangay="{{ $msg->sender?->barangay ?? '—' }}"
                    data-message="{{ $msg->message }}"
                    data-received="{{ $msg->created_at->format('M d, Y g:i A') }}"
                    data-is-read="{{ $msg->is_read ? '1' : '0' }}"
                    data-read-url="{{ route('admin.operations.gobiker-messages.mark-read', $msg) }}">
                    <td>
                        <div class="gobiker-message-sender">
                            <span class="gobiker-message-unread-dot" aria-hidden="true"></span>
                            <span>{{ $msg->sender?->name ?? '—' }}</span>
                        </div>
                    </td>
                    <td>{{ $msg->sender?->barangay ?? '—' }}</td>
                    <td class="gobiker-message-preview" title="{{ $msg->message }}">{{ $msg->message }}</td>
                    <td>
                        <time datetime="{{ $msg->created_at->toIso8601String() }}" title="{{ $msg->created_at->format('M d, Y g:i A') }}">
                            {{ $msg->created_at->diffForHumans() }}
                        </time>
                    </td>
                    <td>
                        <span class="badge {{ $msg->is_read ? 'badge-green' : 'badge-blue' }}" data-message-status>
                            {{ $msg->is_read ? 'Read' : 'Unread' }}
                        </span>
                    </td>
                    <td class="text-right">
                        <div class="gobiker-message-actions">
                            <button type="button" class="gobiker-action gobiker-action-open" data-open-message>
                                <x-admin.icon name="eye" />
                                <span>Read message</span>
                            </button>
                            <form method="POST" action="{{ route('admin.operations.gobiker-messages.destroy', $msg) }}"
                                  data-confirm-title="Delete this message?"
                                  data-confirm-text="This message will be permanently deleted."
                                  data-confirm-ok="Delete message">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="gobiker-action gobiker-action-delete">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="ops-empty">
                        @if(request('search'))
                            No messages match your search and selected filter.
                        @elseif($statusFilter === 'read')
                            No read messages found.
                        @elseif($statusFilter === 'unread')
                            No unread messages. You’re all caught up.
                        @else
                            No messages have been received yet.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $messages->withQueryString()->links('admin.partials.pagination') }}
</section>

<dialog class="gobiker-reader" id="gobikerMessageReader" aria-labelledby="gobiker-reader-title" aria-describedby="gobiker-reader-content">
    <div class="gobiker-reader-inner">
        <header class="gobiker-reader-header">
            <div>
                <span class="gobiker-reader-kicker">GoBiker message</span>
                <h2 id="gobiker-reader-title"></h2>
                <span class="badge" data-reader-status></span>
            </div>
            <button type="button" class="gobiker-reader-close" data-close-reader aria-label="Close message">×</button>
        </header>

        <dl class="gobiker-reader-meta">
            <div>
                <dt>Barangay</dt>
                <dd data-reader-barangay></dd>
            </div>
            <div>
                <dt>Received</dt>
                <dd data-reader-received></dd>
            </div>
        </dl>

        <section class="gobiker-reader-message">
            <h3>Message</h3>
            <p id="gobiker-reader-content" data-reader-message></p>
        </section>
        <p class="gobiker-reader-error" data-reader-error role="alert" hidden></p>
    </div>
</dialog>

<script>
(() => {
    const reader = document.getElementById('gobikerMessageReader');
    if (!reader) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const setText = (selector, value) => {
        const element = reader.querySelector(selector);
        if (element) element.textContent = value || '—';
    };

    document.querySelectorAll('[data-open-message]').forEach((button) => {
        button.addEventListener('click', async () => {
            const row = button.closest('tr');
            if (!row) return;

            setText('[data-reader-title]', row.dataset.sender);
            setText('[data-reader-barangay]', row.dataset.barangay);
            setText('[data-reader-received]', row.dataset.received);
            setText('[data-reader-message]', row.dataset.message);

            const status = reader.querySelector('[data-reader-status]');
            const error = reader.querySelector('[data-reader-error]');
            if (!status || !error) return;

            error.hidden = true;
            error.textContent = '';
            status.textContent = row.dataset.isRead === '1' ? 'Read' : 'Marking as read…';
            status.className = `badge ${row.dataset.isRead === '1' ? 'badge-green' : 'badge-blue'}`;
            reader.showModal();

            if (row.dataset.isRead === '1') return;

            try {
                const response = await fetch(row.dataset.readUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Unable to mark message as read (${response.status})`);
                }

                row.dataset.isRead = '1';
                row.classList.remove('gobiker-message-unread');
                const unreadCount = document.querySelector('[data-unread-count]');
                if (unreadCount) {
                    const count = Number(unreadCount.textContent.replaceAll(',', ''));
                    unreadCount.textContent = new Intl.NumberFormat().format(Math.max(0, count - 1));
                }
                const dot = row.querySelector('.gobiker-message-unread-dot');
                if (dot) dot.remove();
                const rowStatus = row.querySelector('[data-message-status]');
                if (rowStatus) {
                    rowStatus.textContent = 'Read';
                    rowStatus.className = 'badge badge-green';
                }
                status.textContent = 'Read';
                status.className = 'badge badge-green';

                if (new URLSearchParams(window.location.search).get('status') === 'unread') {
                    window.location.reload();
                }
            } catch (readError) {
                console.error(readError);
                status.textContent = 'Unread';
                error.textContent = 'The message is open, but its read status could not be updated. Please try again.';
                error.hidden = false;
            }
        });
    });

    reader.querySelector('[data-close-reader]')?.addEventListener('click', () => reader.close());
    reader.addEventListener('click', (event) => {
        if (event.target === reader) reader.close();
    });
})();
</script>
@endsection
