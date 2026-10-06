@extends('admin.operations.layout')

@section('title', 'GoBiker Messages')

@section('content')
<div class="ops-header gobiker-messages-page">
    <div>
        <h1 class="ops-title">GoBiker Messages</h1>
        <p class="ops-subtitle">Messages sent from GoBikers via the mobile app.</p>
    </div>
</div>

<section class="gobiker-messages-page">
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
        </label>
        <button type="submit" class="gobiker-action gobiker-action-search">Search messages</button>
        @if(request('search'))
            <a href="{{ route('admin.operations.gobiker-messages.index') }}" class="gobiker-action gobiker-action-clear">Clear search</a>
        @endif
    </form>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="ops-table-wrap">
        <table class="ops-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Barangay</th>
                    <th>Message</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                <tr class="{{ $msg->is_read ? '' : 'font-semibold bg-blue-50' }}" id="msg-{{ $msg->id }}">
                    <td>
                        <div class="flex items-center gap-2">
                            @if(!$msg->is_read)
                                <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                            @endif
                            {{ $msg->sender?->name ?? '—' }}
                        </div>
                    </td>
                    <td>{{ $msg->sender?->barangay ?? '—' }}</td>
                    <td class="max-w-sm">
                        <p class="truncate" title="{{ $msg->message }}">{{ $msg->message }}</p>
                    </td>
                    <td>{{ $msg->created_at->diffForHumans() }}</td>
                    <td>
                        @if($msg->is_read)
                            <span class="badge badge-green">Read</span>
                        @else
                            <span class="badge badge-blue">New</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="gobiker-message-actions">
                            @if(!$msg->is_read)
                            <button
                                onclick="markRead(this)"
                                data-read-url="{{ route('admin.operations.gobiker-messages.mark-read', $msg) }}"
                                class="gobiker-action gobiker-action-read"
                                title="Mark as read"
                            >✓ <span>Mark read</span></button>
                            @endif
                            <form method="POST" action="{{ route('admin.operations.gobiker-messages.destroy', $msg) }}"
                                  data-confirm-title="Delete this message?"
                                  data-confirm-text="This message will be permanently deleted."
                                  data-confirm-ok="Delete message">
                                @csrf @method('DELETE')
                                <button type="submit" class="gobiker-action gobiker-action-delete">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="ops-empty">No messages yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $messages->withQueryString()->links('admin.partials.pagination') }}
</section>

<script>
function markRead(button) {
    fetch(button.dataset.readUrl, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
    }).then((response) => {
        if (!response.ok) {
            throw new Error(`Unable to mark message as read (${response.status})`);
        }

        const row = button.closest('tr');
        if (row) {
            row.classList.remove('font-semibold', 'bg-blue-50');
            row.querySelector('.badge-blue')?.replaceWith(
                Object.assign(document.createElement('span'), {
                    className: 'badge badge-green',
                    textContent: 'Read',
                })
            );
            row.querySelector('[onclick]')?.remove();
            const dot = row.querySelector('.rounded-full');
            if (dot) dot.remove();
        }
    }).catch((error) => {
        console.error(error);
        alert('Could not mark the message as read. Please try again.');
    });
}
</script>
@endsection
