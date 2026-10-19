@extends('admin.operations.layout')
@section('title', 'Donations')
@section('heading', 'Donations')
@section('subheading', 'All donation submissions received through the website.')

@section('content')
@php
    $statusColors = [
        'paid'      => 'ok',
        'pending'   => 'warn',
        'failed'    => 'bad',
        'cancelled' => 'neutral',
    ];
    $methodLabels = [
        'paypal'   => 'PayPal',
        'paymongo' => 'PayMongo',
        'gcash'    => 'GCash',
        'card'     => 'Card',
    ];
@endphp

{{-- Summary Cards --}}
<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="dollar-sign" /></span>
        <div>
            <div class="stat-num">${{ number_format($totalPaid, 2) }}</div>
            <div class="stat-label">Total collected (USD)</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="check-circle" /></span>
        <div>
            <div class="stat-num">{{ $totalCount }}</div>
            <div class="stat-label">Paid donations</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="clock" /></span>
        <div>
            <div class="stat-num">{{ $pendingCount }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
</div>

{{-- Toolbar --}}
<div class="toolbar">
    <form class="search-form" role="search" method="GET" action="{{ route('admin.operations.donations.index') }}">
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search donor name or email" aria-label="Search donations">
        <select class="input" name="status" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="paid"      @selected(request('status') === 'paid')>Paid</option>
            <option value="pending"   @selected(request('status') === 'pending')>Pending</option>
            <option value="failed"    @selected(request('status') === 'failed')>Failed</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
        </select>
        <select class="input" name="method" aria-label="Filter by method" onchange="this.form.submit()">
            <option value="">All methods</option>
            <option value="paypal"   @selected(request('method') === 'paypal')>PayPal</option>
            <option value="paymongo" @selected(request('method') === 'paymongo')>PayMongo</option>
        </select>
        <button type="submit" class="btn btn-secondary"><x-admin.icon name="search" /> Search</button>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Donor</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Frequency</th>
                <th>Status</th>
                <th>Date</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($donations as $donation)
            @php
                $statusColor = $statusColors[$donation->status] ?? 'neutral';
                $method = $methodLabels[$donation->payment_method] ?? ucfirst($donation->payment_method);
            @endphp
            <tr class="donation-row" style="cursor:pointer"
                data-id="{{ $donation->id }}"
                data-name="{{ $donation->donor_name }}"
                data-email="{{ $donation->email }}"
                data-phone="{{ $donation->phone ?? '—' }}"
                data-donor-type="{{ ucfirst($donation->donor_type) }}"
                data-org="{{ $donation->org_name ?? '—' }}"
                data-amount-usd="{{ number_format($donation->amount_usd, 2) }}"
                data-amount-php="{{ $donation->amount_php ? number_format($donation->amount_php, 2) : '—' }}"
                data-fee="{{ number_format($donation->platform_fee_usd, 2) }}"
                data-method="{{ $method }}"
                data-frequency="{{ ucfirst($donation->frequency) }}"
                data-status="{{ ucfirst($donation->status) }}"
                data-status-class="{{ $statusColor }}"
                data-address="{{ trim("{$donation->address1}, " . ($donation->address2 ? "{$donation->address2}, " : '') . "{$donation->city}, {$donation->state}, {$donation->country}") }}"
                data-receipt="{{ $donation->receipt_email ?? '—' }}"
                data-date="{{ $donation->created_at->format('M d, Y g:i A') }}"
            >
                <td>
                    <div class="cell-user">
                        @php
                            $initials = collect(preg_split('/\s+/', trim($donation->donor_name)))->filter()->take(2)
                                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        @endphp
                        <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                        <div>
                            <b>{{ $donation->donor_name }}</b>
                            <small>{{ $donation->email }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <b>${{ number_format($donation->amount_usd, 2) }}</b>
                    @if ($donation->amount_php)
                        <small class="muted" style="display:block">₱{{ number_format($donation->amount_php, 2) }}</small>
                    @endif
                </td>
                <td>{{ $method }}</td>
                <td>{{ ucfirst($donation->frequency) }}</td>
                <td><span class="pill pill-{{ $statusColor }}">{{ ucfirst($donation->status) }}</span></td>
                <td>
                    {{ $donation->created_at->format('M j, Y') }}
                    <small class="muted" style="display:block">{{ $donation->created_at->format('g:i A') }}</small>
                </td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="icon-btn btn-view-donation" title="View" aria-label="View donation from {{ $donation->donor_name }}"><x-admin.icon name="eye" /></button>
                    </div>
                </td>
            </tr>
            @empty
            <tr class="is-static">
                <td colspan="7">
                    <div class="empty">
                        <x-admin.icon name="dollar-sign" />
                        <b>No donations found</b>
                        <p>Donations will appear here once submitted through the website.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $donations->links('admin.partials.pagination') }}

{{-- Detail Modal --}}
<dialog id="donationModal" class="msg-modal" style="width:min(580px,94vw);max-width:580px;background:#ffffff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow-2);padding:0;overflow:hidden">
    <div class="msg-inner" style="padding:28px 30px">
        <div class="msg-head" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--line);gap:16px">
            <div>
                <h3 id="dm-name" style="font-size:22px;font-weight:700;color:var(--ink);margin:0 0 6px"></h3>
                <span id="dm-status" class="status-pill"></span>
            </div>
            <button type="button" id="dm-close" class="modal-close-btn" aria-label="Close dialog">
                <x-admin.icon name="x" />
            </button>
        </div>

        <div class="msg-meta" style="grid-template-columns: 1fr 1fr;background:#f8fafc;border:1px solid var(--line);border-radius:10px;padding:18px 20px;gap:14px 20px;margin-bottom:0">
            <div><span>Email</span><b id="dm-email"></b></div>
            <div><span>Phone</span><b id="dm-phone"></b></div>
            <div><span>Donor type</span><b id="dm-donor-type"></b></div>
            <div><span>Organisation</span><b id="dm-org"></b></div>
            <div><span>Amount (USD)</span><b id="dm-amount-usd"></b></div>
            <div><span>Amount (PHP)</span><b id="dm-amount-php"></b></div>
            <div><span>Platform fee</span><b id="dm-fee"></b></div>
            <div><span>Method</span><b id="dm-method"></b></div>
            <div><span>Frequency</span><b id="dm-frequency"></b></div>
            <div><span>Receipt email</span><b id="dm-receipt"></b></div>
            <div style="grid-column:1/-1"><span>Address</span><b id="dm-address"></b></div>
            <div><span>Date submitted</span><b id="dm-date"></b></div>
        </div>
    </div>
</dialog>

<script>
(function () {
    const modal = document.getElementById('donationModal');
    const set = (id, val) => { document.getElementById(id).textContent = val || '—'; };

    document.querySelectorAll('.donation-row, .btn-view-donation').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const row = el.closest('tr') ?? el.closest('.donation-row') ?? el.parentElement.closest('tr');
            if (!row) return;
            const d = row.dataset;
            set('dm-name', d.name);
            set('dm-email', d.email);
            set('dm-phone', d.phone);
            set('dm-donor-type', d.donorType);
            set('dm-org', d.org);
            set('dm-amount-usd', '$' + d.amountUsd);
            set('dm-amount-php', d.amountPhp !== '—' ? '₱' + d.amountPhp : '—');
            set('dm-fee', '$' + d.fee);
            set('dm-method', d.method);
            set('dm-frequency', d.frequency);
            set('dm-receipt', d.receipt);
            set('dm-address', d.address);
            set('dm-date', d.date);

            const statusEl = document.getElementById('dm-status');
            statusEl.textContent = d.status;
            statusEl.className = 'status-pill ' + (d.statusClass === 'ok' ? 'published' : d.statusClass === 'warn' ? 'draft' : 'archived');
            modal.showModal();
        });
    });

    document.getElementById('dm-close').addEventListener('click', () => modal.close());
    modal.addEventListener('click', e => { if (e.target === modal) modal.close(); });
})();
</script>
@endsection
