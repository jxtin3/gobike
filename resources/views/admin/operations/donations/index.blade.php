@extends('admin.operations.layout')
@section('title', 'Donations')
@section('heading', 'Donations')
@section('subheading', 'All donation submissions received through the website.')

@use('App\Models\Donation')

@section('content')
@php
    $hasQuery = filled(request('search')) || filled(request('status')) || filled(request('method')) || filled(request('frequency'));
@endphp

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
            <div class="stat-num">{{ number_format($totalCount) }}</div>
            <div class="stat-label">Paid donations</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="clock" /></span>
        <div>
            <div class="stat-num">{{ number_format($pendingCount) }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="heart" /></span>
        <div>
            <div class="stat-num">{{ number_format($monthlyCount) }}</div>
            <div class="stat-label">Active monthly donors</div>
        </div>
    </div>
</div>

<x-admin.filters :action="route('admin.operations.donations.index')" placeholder="Search donor, organization or email" label="Search donations" :filtered="$hasQuery">
    <select class="input" name="status" aria-label="Filter by status" data-autosubmit>
        <option value="">All statuses</option>
        @foreach (array_keys(Donation::STATUS_PILLS) as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <select class="input" name="method" aria-label="Filter by method" data-autosubmit>
        <option value="">All methods</option>
        @foreach (Donation::METHOD_LABELS as $value => $label)
            <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select class="input" name="frequency" aria-label="Filter by frequency" data-autosubmit>
        <option value="">One-time &amp; monthly</option>
        <option value="monthly" @selected(request('frequency') === 'monthly')>Monthly only</option>
    </select>
</x-admin.filters>

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Donor</th>
                <th class="num">Amount</th>
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
                    $monthly = $donation->isMonthlySubscription();
                    $amount = '$'.number_format($donation->amount_usd, 2);
                @endphp
                <tr class="is-clickable"
                    data-dialog="#donationDialog"
                    data-name="{{ $donation->donor_name }}"
                    data-email="{{ $donation->email }}"
                    data-phone="{{ $donation->phone }}"
                    data-donor-type="{{ ucfirst($donation->donor_type) }}"
                    data-org="{{ $donation->org_name }}"
                    data-amount-usd="{{ $amount }}"
                    data-amount-php="{{ $donation->amount_php ? '₱'.number_format($donation->amount_php, 2) : '' }}"
                    data-fee="${{ number_format($donation->platform_fee_usd, 2) }}"
                    data-method="{{ $donation->method_label }}"
                    data-frequency="{{ $donation->frequency_label }}"
                    data-status="{{ ucfirst($donation->status) }}"
                    data-status-pill="{{ $donation->status_pill }}"
                    data-address="{{ $donation->full_address }}"
                    data-receipt="{{ $donation->receipt_email }}"
                    data-date="{{ $donation->created_at->format('M j, Y g:i A') }}"
                    data-reference="{{ $donation->reference }}"
                    data-monthly="{{ $monthly ? '1' : '0' }}"
                    data-subscription="{{ $donation->subscription_label }}"
                    data-subscription-pill="{{ $donation->subscription_pill }}"
                    data-payments="{{ $donation->payments_count }} ({{ '$'.number_format((float) $donation->payments_sum_amount, 2) }} incl. fees)"
                    data-can-cancel="{{ $donation->canCancelSubscription() ? '1' : '0' }}"
                    data-cancel-url="{{ $donation->canCancelSubscription() ? route('admin.operations.donations.cancel-subscription', $donation) : '' }}"
                    data-delete-url="{{ route('admin.operations.donations.destroy', $donation) }}"
                    data-delete-text="{{ $donation->donor_name }} · {{ $amount }} ({{ $donation->status }}). {{ $donation->canCancelSubscription() ? 'This is a monthly donation: cancel it first so the donor is not charged again. ' : '' }}Paid donations are accounting records, so only delete test entries or mistakes. This cannot be undone.">
                    <td>
                        <div class="cell-user">
                            <x-admin.avatar :name="$donation->donor_name" />
                            <div>
                                <b>{{ $donation->donor_name }}</b>
                                <small>{{ $donation->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="num">
                        <b>{{ $amount }}</b>
                        @if ($donation->amount_php)
                            <small class="sub-line">₱{{ number_format($donation->amount_php, 2) }}</small>
                        @endif
                    </td>
                    <td>{{ $donation->method_label }}</td>
                    <td>
                        {{ $donation->frequency_label }}
                        @if ($donation->subscription_label)
                            <small class="sub-line">{{ $donation->subscription_label }}</small>
                        @endif
                    </td>
                    <td><span class="pill pill-{{ $donation->status_pill }}">{{ ucfirst($donation->status) }}</span></td>
                    <td>
                        {{ $donation->created_at->format('M j, Y') }}
                        <small class="sub-line">{{ $donation->created_at->format('g:i A') }}</small>
                    </td>
                    <td>
                        <div class="row-actions">
                            <button type="button" class="icon-btn" data-open title="Open" aria-label="Open donation from {{ $donation->donor_name }}"><x-admin.icon name="eye" /></button>
                        </div>
                    </td>
                </tr>
            @empty
                @if ($hasQuery)
                    <x-admin.empty :row="7" icon="dollar-sign" title="No donations match" :clear="route('admin.operations.donations.index')">
                        Try a different search, or clear the filters.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="7" icon="dollar-sign" title="No donations yet">
                        Donations will appear here once submitted through the website.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$donations" noun="donation" />
</div>

<x-admin.dialog id="donationDialog" kicker="Donation" field="name">
    <x-slot:sub><span data-pill-field="status"></span> <span data-field="reference"></span></x-slot:sub>

    <dl class="dlg-meta">
        <div><dt>Amount (USD)</dt><dd data-field="amountUsd"></dd></div>
        <div><dt>Amount (PHP)</dt><dd data-field="amountPhp"></dd></div>
        <div><dt>Platform fee</dt><dd data-field="fee"></dd></div>
        <div><dt>Method</dt><dd data-field="method"></dd></div>
        <div><dt>Frequency</dt><dd data-field="frequency"></dd></div>
        <div><dt>Submitted</dt><dd data-field="date"></dd></div>
        <div><dt>Email</dt><dd data-field="email"></dd></div>
        <div><dt>Phone</dt><dd data-field="phone"></dd></div>
        <div><dt>Receipt email</dt><dd data-field="receipt"></dd></div>
        <div><dt>Donor type</dt><dd data-field="donorType"></dd></div>
        <div><dt>Organization</dt><dd data-field="org"></dd></div>
        <div class="dlg-meta-wide"><dt>Address</dt><dd data-field="address"></dd></div>
    </dl>

    <section data-show-field="monthly">
        <h3 class="dlg-label">Monthly donation</h3>
        <dl class="dlg-meta">
            <div><dt>Status</dt><dd><span data-pill-field="subscription"></span></dd></div>
            <div><dt>Payments received</dt><dd data-field="payments"></dd></div>
        </dl>
        <form method="POST" data-action-field="cancelUrl" data-show-field="canCancel"
              data-confirm-title="Cancel this monthly donation?"
              data-confirm-text="PayPal will stop charging the donor right away. Donations already made are not refunded."
              data-confirm-ok="Cancel monthly donation">
            @csrf
            <button type="submit" class="btn btn-danger"><x-admin.icon name="x" /> Cancel monthly donation</button>
        </form>
    </section>

    <x-slot:foot>
        <form method="POST" class="dlg-foot-danger" data-action-field="deleteUrl" data-confirm-field="deleteText"
              data-confirm-title="Delete this donation?" data-confirm-ok="Delete donation">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Delete</button>
        </form>
        <div class="dlg-foot-main">
            <button type="button" class="btn btn-secondary" data-dialog-close>Close</button>
        </div>
    </x-slot:foot>
</x-admin.dialog>
@endsection
