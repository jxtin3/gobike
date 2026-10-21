@extends('admin.operations.layout')
@section('title', 'Chapter Inquiries')
@section('heading', 'Chapter Inquiries')
@section('subheading', 'Organizations that want to replicate the Go Bike Project, from the Replicate Go Bike page.')

@section('content')
@php
    $statusColors = [
        'pending'   => 'warn',
        'contacted' => 'info',
        'scheduled' => 'info',
        'completed' => 'ok',
        'cancelled' => 'neutral',
    ];
    $paymentColors = [
        'unpaid'   => 'neutral',
        'pending'  => 'warn',
        'paid'     => 'ok',
        'refunded' => 'bad',
    ];
    $paymentLabels = [
        'unpaid'   => 'Not paid',
        'pending'  => 'Awaiting payment',
        'paid'     => 'Paid',
        'refunded' => 'Refunded',
    ];
@endphp

{{-- Summary Cards --}}
<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="navigation" /></span>
        <div>
            <div class="stat-num">{{ $totalCount }}</div>
            <div class="stat-label">Total inquiries</div>
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
        <span class="stat-icon"><x-admin.icon name="dollar-sign" /></span>
        <div>
            <div class="stat-num">₱{{ number_format($paidTotal, 2) }}</div>
            <div class="stat-label">{{ $paidCount }} paid {{ Str::plural('training', $paidCount) }}</div>
        </div>
    </div>
</div>

{{-- Toolbar --}}
<div class="toolbar">
    <form class="search-form" role="search" method="GET" action="{{ route('admin.operations.chapter-inquiries.index') }}">
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search organization, contact, email or town" aria-label="Search chapter inquiries">
        <select class="input" name="status" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach (\App\Models\ReplicationRequest::STATUSES as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select class="input" name="payment" aria-label="Filter by payment" onchange="this.form.submit()">
            <option value="">All payments</option>
            @foreach ($paymentLabels as $value => $label)
                <option value="{{ $value }}" @selected(request('payment') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary"><x-admin.icon name="search" /> Search</button>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Organization</th>
                <th>Location</th>
                <th>Trainees</th>
                <th>Fee</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Submitted</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inquiries as $inquiry)
            <tr>
                <td>
                    <div class="cell-user">
                        @php
                            $initials = collect(preg_split('/\s+/', trim($inquiry->organization)))->filter()->take(2)
                                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        @endphp
                        <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                        <div>
                            <b>{{ $inquiry->organization }}</b>
                            <small>{{ $inquiry->contact_name }} &middot; {{ $inquiry->reference }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    {{ $inquiry->municipality }}
                    <small class="muted" style="display:block">{{ $inquiry->province }}</small>
                </td>
                <td>{{ $inquiry->trainees }}</td>
                <td>₱{{ number_format((float) $inquiry->amount_php, 2) }}</td>
                <td><span class="pill pill-{{ $paymentColors[$inquiry->payment_status] ?? 'neutral' }}">{{ $paymentLabels[$inquiry->payment_status] ?? ucfirst($inquiry->payment_status) }}</span></td>
                <td><span class="pill pill-{{ $statusColors[$inquiry->status] ?? 'neutral' }}">{{ ucfirst($inquiry->status) }}</span></td>
                <td>
                    {{ $inquiry->created_at->format('M j, Y') }}
                    <small class="muted" style="display:block">{{ $inquiry->created_at->format('g:i A') }}</small>
                </td>
                <td>
                    <div class="row-actions">
                        <a class="icon-btn" href="{{ route('admin.operations.chapter-inquiries.show', $inquiry) }}" title="View" aria-label="View inquiry from {{ $inquiry->organization }}"><x-admin.icon name="eye" /></a>
                    </div>
                </td>
            </tr>
            @empty
            <tr class="is-static">
                <td colspan="8">
                    <div class="empty">
                        <x-admin.icon name="navigation" />
                        <b>No chapter inquiries yet</b>
                        <p>Inquiries sent from the Replicate Go Bike page will appear here.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $inquiries->links('admin.partials.pagination') }}
@endsection
