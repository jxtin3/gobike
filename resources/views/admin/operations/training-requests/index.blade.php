@extends('admin.operations.layout')
@section('title', 'Training Requests')
@section('heading', 'Training Plan Requests')
@section('subheading', 'House-to-house training session requests submitted through the website.')

@section('content')
@php
    $tierColors = [
        'basic'    => 'ok',
        'standard' => 'info',
        'advance'  => 'warn',
    ];
    $statusColors = [
        'pending'   => 'warn',
        'contacted' => 'info',
        'completed' => 'ok',
        'cancelled' => 'neutral',
    ];
@endphp

{{-- Summary Cards --}}
<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="clipboard" /></span>
        <div>
            <div class="stat-num">{{ $totalCount }}</div>
            <div class="stat-label">Total requests</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="clock" /></span>
        <div>
            <div class="stat-num">{{ $pendingCount }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="phone-call" /></span>
        <div>
            <div class="stat-num">{{ $contactedCount }}</div>
            <div class="stat-label">Contacted</div>
        </div>
    </div>
</div>

{{-- Toolbar --}}
<div class="toolbar">
    <form class="search-form" role="search" method="GET" action="{{ route('admin.operations.training-requests.index') }}">
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email or address" aria-label="Search training requests">
        <select class="input" name="tier" aria-label="Filter by tier" onchange="this.form.submit()">
            <option value="">All tiers</option>
            <option value="basic"    @selected(request('tier') === 'basic')>Basic (Free)</option>
            <option value="standard" @selected(request('tier') === 'standard')>Standard (₱499)</option>
            <option value="advance"  @selected(request('tier') === 'advance')>Advance (₱999)</option>
        </select>
        <select class="input" name="status" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="pending"   @selected(request('status') === 'pending')>Pending</option>
            <option value="contacted" @selected(request('status') === 'contacted')>Contacted</option>
            <option value="completed" @selected(request('status') === 'completed')>Completed</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
        </select>
        <button type="submit" class="btn btn-secondary"><x-admin.icon name="search" /> Search</button>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Requester</th>
                <th>Tier</th>
                <th>Address</th>
                <th>Status</th>
                <th>Submitted</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $req)
            @php
                $tierColor   = $tierColors[$req->tier]   ?? 'neutral';
                $statusColor = $statusColors[$req->status] ?? 'neutral';
                $tierLabel   = ['basic' => 'Basic (Free)', 'standard' => 'Standard ₱499', 'advance' => 'Advance ₱999'][$req->tier] ?? ucfirst($req->tier);
            @endphp
            <tr class="training-row" style="cursor:pointer"
                data-name="{{ $req->name }}"
                data-email="{{ $req->email }}"
                data-phone="{{ $req->phone ?? '—' }}"
                data-tier="{{ $tierLabel }}"
                data-tier-class="{{ $tierColor }}"
                data-address="{{ $req->address }}"
                data-message="{{ $req->message ?? '—' }}"
                data-status="{{ ucfirst($req->status) }}"
                data-status-class="{{ $statusColor }}"
                data-date="{{ $req->created_at->format('M d, Y g:i A') }}"
                data-update-url="{{ route('admin.operations.training-requests.status', $req) }}"
            >
                <td>
                    <div class="cell-user">
                        @php
                            $initials = collect(preg_split('/\s+/', trim($req->name)))->filter()->take(2)
                                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        @endphp
                        <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                        <div>
                            <b>{{ $req->name }}</b>
                            <small>{{ $req->email }}</small>
                        </div>
                    </div>
                </td>
                <td><span class="pill pill-{{ $tierColor }}">{{ $tierLabel }}</span></td>
                <td>{{ Str::limit($req->address, 40) }}</td>
                <td><span class="pill pill-{{ $statusColor }}">{{ ucfirst($req->status) }}</span></td>
                <td>
                    {{ $req->created_at->format('M j, Y') }}
                    <small class="muted" style="display:block">{{ $req->created_at->format('g:i A') }}</small>
                </td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="icon-btn btn-view-training" title="View" aria-label="View request from {{ $req->name }}"><x-admin.icon name="eye" /></button>
                    </div>
                </td>
            </tr>
            @empty
            <tr class="is-static">
                <td colspan="6">
                    <div class="empty">
                        <x-admin.icon name="clipboard" />
                        <b>No training requests yet</b>
                        <p>Requests submitted through the Programs page will appear here.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $requests->links('admin.partials.pagination') }}

{{-- Detail Modal --}}
<dialog id="trainingModal" class="msg-modal" style="width:min(580px,94vw);max-width:580px;background:#ffffff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow-2);padding:0;overflow:hidden">
    <div class="msg-inner" style="padding:28px 30px">
        <div class="msg-head" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--line);gap:16px">
            <div>
                <h3 id="tm-name" style="font-size:22px;font-weight:700;color:var(--ink);margin:0 0 6px"></h3>
                <span id="tm-status" class="status-pill"></span>
            </div>
            <button type="button" id="tm-close" class="modal-close-btn" aria-label="Close dialog">
                <x-admin.icon name="x" />
            </button>
        </div>

        <div class="msg-meta" style="grid-template-columns:1fr 1fr">
            <div><span>Email</span><b id="tm-email"></b></div>
            <div><span>Phone</span><b id="tm-phone"></b></div>
            <div><span>Tier</span><b id="tm-tier"></b></div>
            <div><span>Submitted</span><b id="tm-date"></b></div>
            <div style="grid-column:1/-1"><span>Address</span><b id="tm-address"></b></div>
        </div>

        <div class="msg-body-wrapper">
            <span class="msg-body-label">Message / Preferred Schedule</span>
            <p id="tm-message" class="msg-body"></p>
        </div>

        <div class="msg-foot" style="justify-content:space-between;align-items:center">
            <span style="font-size:12px;color:var(--ink-3)">Update status:</span>
            <form id="tm-status-form" method="POST" action="" style="display:flex;gap:8px;flex-wrap:wrap">
                @csrf
                @method('PATCH')
                <button type="submit" name="status" value="pending"   class="btn btn-sm btn-secondary">Pending</button>
                <button type="submit" name="status" value="contacted" class="btn btn-sm btn-secondary">Contacted</button>
                <button type="submit" name="status" value="completed" class="btn btn-sm" style="background:var(--ok);color:#fff">Completed</button>
                <button type="submit" name="status" value="cancelled" class="btn btn-sm" style="background:var(--bad);color:#fff">Cancelled</button>
            </form>
        </div>
    </div>
</dialog>

<script>
(function () {
    const modal = document.getElementById('trainingModal');
    const set = (id, val) => { document.getElementById(id).textContent = val || '—'; };

    document.querySelectorAll('.training-row, .btn-view-training').forEach(function (el) {
        el.addEventListener('click', function () {
            const row = el.tagName === 'TR' ? el : el.closest('tr');
            if (!row) return;
            const d = row.dataset;
            set('tm-name', d.name);
            set('tm-email', d.email);
            set('tm-phone', d.phone);
            set('tm-tier', d.tier);
            set('tm-address', d.address);
            set('tm-message', d.message);
            set('tm-date', d.date);

            const statusEl = document.getElementById('tm-status');
            statusEl.textContent = d.status;
            statusEl.className = 'status-pill ' + (d.statusClass === 'ok' ? 'published' : d.statusClass === 'info' ? 'scheduled' : d.statusClass === 'warn' ? 'draft' : 'archived');

            document.getElementById('tm-status-form').action = d.updateUrl;
            modal.showModal();
        });
    });

    document.getElementById('tm-close').addEventListener('click', () => modal.close());
    modal.addEventListener('click', e => { if (e.target === modal) modal.close(); });
})();
</script>
@endsection
