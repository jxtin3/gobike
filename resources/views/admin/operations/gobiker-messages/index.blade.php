@extends('admin.operations.layout')

@section('title', 'GoBiker Messages')

@section('content')
<div class="ops-header">
    <div>
        <h1 class="ops-title">GoBiker Messages</h1>
        <p class="ops-subtitle">Messages sent from GoBikers via the mobile app.</p>
    </div>
</div>

{{-- Search --}}
<form method="GET" class="ops-search-row" style="margin-bottom:1.25rem;">
    <input
        type="search"
        name="search"
        value="{{ request('search') }}"
        placeholder="Search by name or message…"
        class="ops-search-input"
    >
    <button type="submit" class="btn-primary-sm">Search</button>
    @if(request('search'))
        <a href="{{ route('admin.operations.gobiker-messages.index') }}" class="btn-ghost-sm">Clear</a>
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
                    <div class="flex justify-end gap-2">
                        @if(!$msg->is_read)
                        <button
                            onclick="markRead({{ $msg->id }})"
                            class="btn-ghost-sm"
                            title="Mark as read"
                        >✓ Read</button>
                        @endif
                        <form method="POST" action="{{ route('admin.operations.gobiker-messages.destroy', $msg) }}"
                              onsubmit="return confirm('Delete this message?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger-sm">Delete</button>
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

<script>
function markRead(id) {
    fetch(`/admin/operations/gobiker-messages/${id}/read`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
    }).then(() => {
        const row = document.getElementById('msg-' + id);
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
    });
}
</script>
@endsection
