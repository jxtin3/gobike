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
        'gcash'    => 'GCash',
        'bank'     => 'Online banking',
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
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="heart" /></span>
        <div>
            <div class="stat-num">{{ $monthlyCount }}</div>
            <div class="stat-label">Active monthly donors</div>
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
            @foreach ($methodLabels as $value => $label)
                <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select class="input" name="frequency" aria-label="Filter by frequency" onchange="this.form.submit()">
            <option value="">One-time &amp; monthly</option>
            <option value="monthly" @selected(request('frequency') === 'monthly')>Monthly only</option>
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
                data-reference="{{ $donation->reference }}"
                data-monthly="{{ $donation->isMonthlySubscription() ? '1' : '0' }}"
                data-subscription="{{ $donation->subscription_label ?? '' }}"
                data-payments="{{ $donation->payments_count }} ({{ '$'.number_format((float) $donation->payments_sum_amount, 2) }})"
                data-subscription-class="{{ ['active' => 'ok', 'suspended' => 'warn'][$donation->subscription_status ?? 'active'] ?? 'neutral' }}"
                data-cancel-url="{{ $donation->canCancelSubscription() ? route('admin.operations.donations.cancel-subscription', $donation) : '' }}"
                data-delete-url="{{ route('admin.operations.donations.destroy', $donation) }}"
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
                <td>
                    {{ ucfirst($donation->frequency) }}
                    @if ($donation->subscription_label)
                        <small class="muted" style="display:block">{{ $donation->subscription_label }}</small>
                    @endif
                </td>
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
<dialog id="donationModal" class="msg-modal don-modal" aria-labelledby="dm-name">
    <div class="msg-inner">
        <div class="msg-head">
            <div>
                <h3 id="dm-name"></h3>
                <span id="dm-status" class="status-pill"></span>
            </div>
            <button type="button" id="dm-close" class="modal-close-btn" aria-label="Close dialog">
                <x-admin.icon name="x" />
            </button>
        </div>

        <div class="msg-meta don-meta">
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
            <div class="don-wide"><span>Address</span><b id="dm-address"></b></div>
            <div><span>Date submitted</span><b id="dm-date"></b></div>
            <div><span>Reference</span><b id="dm-reference"></b></div>
        </div>

        {{-- Monthly (PayPal subscription) details, shown only for monthly donations --}}
        <section id="dm-monthly" class="don-monthly" hidden>
            <h4 class="don-monthly-title">Monthly donation</h4>
            <div class="don-monthly-grid">
                <div><span class="don-label">Status</span><b><span id="dm-subscription" class="pill"></span></b></div>
                <div><span class="don-label">Payments received</span><b id="dm-payments"></b></div>
            </div>
            <form id="dm-cancel-form" method="POST" action="" hidden
                  data-confirm-title="Cancel this monthly donation?"
                  data-confirm-text="PayPal will stop charging the donor right away. Donations already made are not refunded."
                  data-confirm-ok="Cancel monthly donation">
                @csrf
                <button type="submit" class="btn btn-soft-danger"><x-admin.icon name="x" /> Cancel monthly donation</button>
            </form>
        </section>

        <div class="msg-foot don-foot">
            <form id="dm-delete-form" method="POST" action="" data-confirm-title="Delete this donation?" data-confirm-ok="Delete donation">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-soft-danger"><x-admin.icon name="trash" /> Delete donation</button>
            </form>
            <button type="button" class="btn btn-secondary" id="dm-close-2">Close</button>
        </div>
    </div>
</dialog>

<script>
(function () {
    const modal = document.getElementById('donationModal');
    const set = (id, val) => { document.getElementById(id).textContent = val || '—'; };

    document.querySelectorAll('.donation-row').forEach(function (row) {
        row.addEventListener('click', function () {
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
            set('dm-reference', d.reference);

            const statusEl = document.getElementById('dm-status');
            statusEl.textContent = d.status;
            statusEl.className = 'status-pill ' + (d.statusClass === 'ok' ? 'published' : d.statusClass === 'warn' ? 'draft' : 'archived');

            // Monthly (PayPal subscription) section
            const monthly = d.monthly === '1';
            document.getElementById('dm-monthly').hidden = !monthly;
            set('dm-payments', d.payments);
            const sub = document.getElementById('dm-subscription');
            sub.textContent = d.subscription || '—';
            sub.className = 'pill pill-' + (d.subscriptionClass || 'neutral');
            const cancelForm = document.getElementById('dm-cancel-form');
            cancelForm.hidden = !monthly || !d.cancelUrl;
            cancelForm.action = d.cancelUrl || '';

            // Delete (asks for confirmation first, through the shared admin confirm dialog)
            const deleteForm = document.getElementById('dm-delete-form');
            deleteForm.action = d.deleteUrl;
            deleteForm.dataset.confirmText = d.name + ' · $' + d.amountUsd + ' (' + d.status + '). '
                + (monthly && d.cancelUrl ? 'This is a monthly donation: cancel it first so the donor is not charged again. ' : '')
                + 'Paid donations are accounting records, so only delete test entries or mistakes. This cannot be undone.';

            modal.showModal();
        });
    });

    const close = () => modal.close();
    document.getElementById('dm-close').addEventListener('click', close);
    document.getElementById('dm-close-2').addEventListener('click', close);
    modal.addEventListener('click', e => { if (e.target === modal) close(); });
})();
</script>
@endsection
