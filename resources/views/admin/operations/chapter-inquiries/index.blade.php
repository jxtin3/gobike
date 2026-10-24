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

<x-admin.filters :action="route('admin.operations.chapter-inquiries.index')" placeholder="Search organization, contact, email or town" label="Search chapter inquiries" :filtered="$hasQuery">
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
</x-admin.filters>

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Organization</th>
                <th>Location</th>
                <th class="num">Trainees</th>
                <th class="num">Fee</th>
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
                        <x-admin.avatar :name="$inquiry->organization" />
                        <div>
                            <b><a class="cell-link" href="{{ route('admin.operations.chapter-inquiries.show', $inquiry) }}" title="{{ $inquiry->organization }}" data-skeleton="detail">{{ Str::limit($inquiry->organization, 48) }}</a></b>
                            <small>{{ Str::limit($inquiry->contact_name, 32) }} &middot; {{ $inquiry->reference }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    {{ $inquiry->municipality }}
                    <small class="sub-line">{{ $inquiry->province }}</small>
                </td>
                <td class="num">{{ $inquiry->trainees }}</td>
                <td class="num">₱{{ number_format((float) $inquiry->amount_php, 2) }}</td>
                <td><span class="pill pill-{{ $inquiry->payment_pill }}">{{ $inquiry->payment_label }}</span></td>
                <td><span class="pill pill-{{ $inquiry->status_pill }}">{{ $inquiry->status_label }}</span></td>
                <td>
                    {{ $inquiry->created_at->format('M j, Y') }}
                    <small class="sub-line">{{ $inquiry->created_at->format('g:i A') }}</small>
                </td>
                <td>
                    <div class="row-actions">
                        <a class="icon-btn" href="{{ route('admin.operations.chapter-inquiries.show', $inquiry) }}" title="View" aria-label="View inquiry from {{ $inquiry->organization }}" data-skeleton="detail"><x-admin.icon name="eye" /></a>
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
                @if ($hasQuery)
                    <x-admin.empty :row="8" icon="navigation" title="No inquiries match" :clear="route('admin.operations.chapter-inquiries.index')">
                        Try a different search, or clear the filters.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="8" icon="navigation" title="No chapter inquiries yet">
                        Inquiries sent from the Replicate Go Bike page will appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$inquiries" noun="inquiry" />
</div>
@endsection
