@extends('admin.operations.layout')
@section('title', 'Training requests')
@section('heading', 'Training plan requests')
@section('subheading', 'House-to-house training session requests submitted through the website.')

@use('App\Models\TrainingRequest')

@section('content')
@php
    $hasQuery = filled(request('search')) || filled(request('tier')) || filled(request('status'));
@endphp

<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="clipboard" /></span>
        <div>
            <div class="stat-num">{{ number_format($totalCount) }}</div>
            <div class="stat-label">Total requests</div>
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
        <span class="stat-icon"><x-admin.icon name="phone-call" /></span>
        <div>
            <div class="stat-num">{{ number_format($contactedCount) }}</div>
            <div class="stat-label">Contacted</div>
        </div>
    </div>
</div>

<div class="toolbar">
    <form class="search-form" role="search" method="GET" action="{{ route('admin.operations.training-requests.index') }}">
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email or address" aria-label="Search training requests">
        <select class="input" name="tier" aria-label="Filter by tier" data-autosubmit>
            <option value="">All tiers</option>
            @foreach (TrainingRequest::TIERS as $value => $label)
                <option value="{{ $value }}" @selected(request('tier') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select class="input" name="status" aria-label="Filter by status" data-autosubmit>
            <option value="">All statuses</option>
            @foreach (TrainingRequest::STATUSES as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
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
                    $initials = collect(preg_split('/\s+/', trim($req->name)))->filter()->take(2)
                        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                @endphp
                <tr class="is-clickable"
                    data-dialog="#trainingDialog"
                    data-name="{{ $req->name }}"
                    data-email="{{ $req->email }}"
                    data-mailto="mailto:{{ $req->email }}?subject={{ rawurlencode('Your Go Bike training request') }}"
                    data-phone="{{ $req->phone }}"
                    data-tier="{{ $req->tier_label }}"
                    data-address="{{ $req->address }}"
                    data-message="{{ $req->message }}"
                    data-status="{{ $req->status_label }}"
                    data-status-pill="{{ $req->status_pill }}"
                    data-status-value="{{ $req->status }}"
                    data-date="{{ $req->created_at->format('M j, Y g:i A') }}"
                    data-update-url="{{ route('admin.operations.training-requests.status', $req) }}"
                    data-delete-url="{{ route('admin.operations.training-requests.destroy', $req) }}"
                    data-delete-text="The request from {{ $req->name }} will be permanently deleted.">
                    <td>
                        <div class="cell-user">
                            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                            <div>
                                <b>{{ $req->name }}</b>
                                <small>{{ $req->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="pill pill-{{ $req->tier_pill }}">{{ $req->tier_label }}</span></td>
                    <td title="{{ $req->address }}">{{ Str::limit($req->address, 40) }}</td>
                    <td><span class="pill pill-{{ $req->status_pill }}">{{ $req->status_label }}</span></td>
                    <td>
                        {{ $req->created_at->format('M j, Y') }}
                        <small class="sub-line">{{ $req->created_at->format('g:i A') }}</small>
                    </td>
                    <td>
                        <div class="row-actions">
                            <button type="button" class="icon-btn" data-open title="Open" aria-label="Open request from {{ $req->name }}"><x-admin.icon name="eye" /></button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="is-static">
                    <td colspan="6">
                        <div class="empty">
                            <x-admin.icon name="clipboard" />
                            @if ($hasQuery)
                                <b>No requests match</b>
                                <p>Try a different search, or clear the filters.</p>
                                <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.training-requests.index') }}">Clear filters</a>
                            @else
                                <b>No training requests yet</b>
                                <p>Requests submitted through the Programs page will appear here.</p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $requests->links('admin.partials.pagination') }}

<dialog id="trainingDialog" class="msg-modal" aria-labelledby="training-dialog-title">
    <div class="msg-inner">
        <header class="msg-head">
            <div>
                <span class="msg-kicker">Training request</span>
                <h2 id="training-dialog-title" data-field="name"></h2>
                <span data-pill-field="status"></span>
            </div>
            <button type="button" class="icon-btn" data-dialog-close aria-label="Close"><x-admin.icon name="x" /></button>
        </header>

        <dl class="msg-meta">
            <div><dt>Email</dt><dd data-field="email"></dd></div>
            <div><dt>Phone</dt><dd data-field="phone"></dd></div>
            <div><dt>Tier</dt><dd data-field="tier"></dd></div>
            <div><dt>Submitted</dt><dd data-field="date"></dd></div>
            <div class="msg-meta-wide"><dt>Address</dt><dd data-field="address"></dd></div>
        </dl>

        <section class="msg-body-wrapper">
            <h3 class="msg-body-label">Message and preferred schedule</h3>
            <p class="msg-body" data-field="message"></p>
        </section>

        <form method="POST" class="msg-status" data-action-field="updateUrl" data-pressed-field="statusValue">
            @csrf
            @method('PATCH')
            <span class="msg-status-label" id="training-status-label">Set status</span>
            <div class="msg-status-buttons" role="group" aria-labelledby="training-status-label">
                <button type="submit" name="status" value="pending" class="btn btn-sm btn-secondary">Pending</button>
                <button type="submit" name="status" value="contacted" class="btn btn-sm btn-secondary">Contacted</button>
                <button type="submit" name="status" value="completed" class="btn btn-sm btn-secondary">Completed</button>
                <button type="submit" name="status" value="cancelled" class="btn btn-sm btn-secondary"
                        data-confirm-title="Cancel this training request?"
                        data-confirm-text="The request will be marked cancelled. You can change the status again later."
                        data-confirm-ok="Mark cancelled">Cancelled</button>
            </div>
        </form>

        <footer class="msg-foot">
            <form method="POST" data-action-field="deleteUrl" data-confirm-field="deleteText"
                  data-confirm-title="Delete this training request?" data-confirm-ok="Delete request">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-soft-danger"><x-admin.icon name="trash" /> Delete</button>
            </form>
            <a class="btn btn-primary" data-href-field="mailto"><x-admin.icon name="mail" /> Email requester</a>
        </footer>
    </div>
</dialog>
@endsection
