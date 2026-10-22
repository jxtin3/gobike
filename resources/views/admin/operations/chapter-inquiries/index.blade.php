@extends('admin.operations.layout')
@section('title', 'Chapter inquiries')
@section('heading', 'Chapter inquiries')
@section('subheading', 'Organizations that want to replicate the Go Bike Project, from the Replicate Go Bike page.')

@use('App\Models\ReplicationRequest')

@section('content')
@php
    $hasQuery = filled(request('search')) || filled(request('status')) || filled(request('payment'));
@endphp

{{-- Summary Cards --}}
<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="navigation" /></span>
        <div>
            <div class="stat-num">{{ number_format($totalCount) }}</div>
            <div class="stat-label">Total inquiries</div>
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
        <select class="input" name="status" aria-label="Filter by status" data-autosubmit>
            <option value="">All statuses</option>
            @foreach (ReplicationRequest::STATUSES as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select class="input" name="payment" aria-label="Filter by payment" data-autosubmit>
            <option value="">All payments</option>
            @foreach (ReplicationRequest::PAYMENT_LABELS as $value => $label)
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
            @php
                $initials = collect(preg_split('/\s+/', trim($inquiry->organization)))->filter()->take(2)
                    ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
            @endphp
            <tr>
                <td>
                    <div class="cell-user">
                        <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                        <div>
                            <b><a class="cell-link" href="{{ route('admin.operations.chapter-inquiries.show', $inquiry) }}" title="{{ $inquiry->organization }}">{{ Str::limit($inquiry->organization, 48) }}</a></b>
                            <small>{{ Str::limit($inquiry->contact_name, 32) }} &middot; {{ $inquiry->reference }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    {{ $inquiry->municipality }}
                    <small class="sub-line">{{ $inquiry->province }}</small>
                </td>
                <td>{{ $inquiry->trainees }}</td>
                <td>₱{{ number_format((float) $inquiry->amount_php, 2) }}</td>
                <td><span class="pill pill-{{ $inquiry->payment_pill }}">{{ $inquiry->payment_label }}</span></td>
                <td><span class="pill pill-{{ $inquiry->status_pill }}">{{ $inquiry->status_label }}</span></td>
                <td>
                    {{ $inquiry->created_at->format('M j, Y') }}
                    <small class="sub-line">{{ $inquiry->created_at->format('g:i A') }}</small>
                </td>
                <td>
                    <div class="row-actions">
                        <a class="icon-btn" href="{{ route('admin.operations.chapter-inquiries.show', $inquiry) }}" title="View" aria-label="View inquiry from {{ $inquiry->organization }}"><x-admin.icon name="eye" /></a>
                        @unless ($inquiry->isPaid())
                            <form method="POST" action="{{ route('admin.operations.chapter-inquiries.destroy', $inquiry) }}"
                                  data-confirm-title="Delete this inquiry?"
                                  data-confirm-text="The inquiry from {{ $inquiry->organization }} will be permanently deleted."
                                  data-confirm-ok="Delete inquiry">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete inquiry from {{ $inquiry->organization }}"><x-admin.icon name="trash" /></button>
                            </form>
                        @endunless
                    </div>
                </td>
            </tr>
            @empty
            <tr class="is-static">
                <td colspan="8">
                    <div class="empty">
                        <x-admin.icon name="navigation" />
                        @if ($hasQuery)
                            <b>No inquiries match</b>
                            <p>Try a different search, or clear the filters.</p>
                            <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.chapter-inquiries.index') }}">Clear filters</a>
                        @else
                            <b>No chapter inquiries yet</b>
                            <p>Inquiries sent from the Replicate Go Bike page will appear here.</p>
                        @endif
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $inquiries->links('admin.partials.pagination') }}
@endsection
