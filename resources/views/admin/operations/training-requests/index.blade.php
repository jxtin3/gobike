@extends('admin.operations.layout')
@section('title', 'Training plan requests')
@section('heading', 'Training plan requests')
@section('subheading', 'Individual first aid training requested through the website. Each one starts on the date the person chose.')

@use('App\Models\TrainingRequest')

@section('content')
@php
    $hasQuery = filled(request('search')) || filled(request('tier')) || filled(request('status')) || filled($when);
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
        <span class="stat-icon"><x-admin.icon name="clock" /></span>
        <div>
            <div class="stat-num">{{ number_format($upcomingCount) }}</div>
            <div class="stat-label">Upcoming trainings</div>
        </div>
    </div>
</div>

<x-admin.filters :action="route('admin.operations.training-requests.index')" placeholder="Search name, email or address" label="Search training requests" :filtered="$hasQuery">
    <select class="input" name="tier" aria-label="Filter by plan" data-autosubmit>
        <option value="">All plans</option>
        @foreach ($tiers as $value => $label)
            <option value="{{ $value }}" @selected(request('tier') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select class="input" name="status" aria-label="Filter by status" data-autosubmit>
        <option value="">All statuses</option>
        @foreach (TrainingRequest::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <select class="input" name="when" aria-label="Filter by training date" data-autosubmit>
        <option value="">Any training date</option>
        <option value="upcoming" @selected($when === 'upcoming')>Upcoming (soonest first)</option>
        <option value="today" @selected($when === 'today')>Today</option>
        <option value="past" @selected($when === 'past')>Past dates</option>
    </select>
</x-admin.filters>

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Requester</th>
                <th>Plan</th>
                <th>Training date</th>
                <th>Address</th>
                <th>Status</th>
                <th>Submitted</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $req)
                <tr class="is-clickable"
                    data-dialog="#trainingDialog"
                    data-name="{{ $req->name }}"
                    data-email="{{ $req->email }}"
                    data-mailto="mailto:{{ $req->email }}?subject={{ rawurlencode('Your Go Bike training request') }}"
                    data-phone="{{ $req->phone }}"
                    data-tier="{{ $req->tier_label }}"
                    data-schedule="{{ $req->schedule_label }}"
                    data-address="{{ $req->address }}"
                    data-message="{{ $req->message }}"
                    data-no-id="{{ empty($req->id_paths) ? 1 : 0 }}"
                    @foreach ($req->id_paths ?? [] as $i => $path)
                        data-id-url{{ $i }}="{{ route('admin.operations.training-requests.id', [$req, $i]) }}"
                        @if (\Illuminate\Support\Str::endsWith(strtolower($path), ['.jpg', '.jpeg', '.png', '.webp']))
                            data-id-img{{ $i }}="{{ route('admin.operations.training-requests.id', [$req, $i]) }}"
                        @else
                            data-id-pdf{{ $i }}="1"
                        @endif
                    @endforeach
                    data-status="{{ $req->status_label }}"
                    data-status-pill="{{ $req->status_pill }}"
                    data-status-value="{{ $req->status }}"
                    data-date="{{ $req->created_at->format('M j, Y g:i A') }}"
                    data-update-url="{{ route('admin.operations.training-requests.status', $req) }}"
                    data-delete-url="{{ route('admin.operations.training-requests.destroy', $req) }}"
                    data-delete-text="The request from {{ $req->name }} will be permanently deleted.">
                    <td>
                        <div class="cell-user">
                            <x-admin.avatar :name="$req->name" />
                            <div>
                                <b>{{ $req->name }}</b>
                                <small>{{ $req->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="pill pill-{{ $req->tier_pill }}">{{ $req->tier_label }}</span></td>
                    <td>
                        @if ($req->preferred_date)
                            {{ $req->preferred_date->format('D, M j') }}
                            <small class="sub-line">{{ ucfirst($req->preferred_window) }}{{ $req->preferred_date->isToday() ? ' · today' : '' }}</small>
                        @else
                            <span class="muted">Not set</span>
                        @endif
                    </td>
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
                @if ($hasQuery)
                    <x-admin.empty :row="7" icon="clipboard" title="No requests match" :clear="route('admin.operations.training-requests.index')">
                        Try a different search, or clear the filters.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="7" icon="clipboard" title="No training requests yet">
                        Requests submitted through the Programs page will appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$requests" noun="request" />
</div>

<x-admin.dialog id="trainingDialog" kicker="Training request" field="name">
    <x-slot:sub><span data-pill-field="status"></span></x-slot:sub>

    <dl class="dlg-meta">
        <div><dt>Email</dt><dd data-field="email"></dd></div>
        <div><dt>Phone</dt><dd data-field="phone"></dd></div>
        <div><dt>Plan</dt><dd data-field="tier"></dd></div>
        <div><dt>Training date</dt><dd data-field="schedule"></dd></div>
        <div><dt>Submitted</dt><dd data-field="date"></dd></div>
        <div class="dlg-meta-wide"><dt>Address</dt><dd data-field="address"></dd></div>
    </dl>

    <section>
        <h3 class="dlg-label">Identity verification</h3>
        <ul class="id-proofs">
            @for ($i = 0; $i < TrainingRequest::MAX_ID_FILES; $i++)
                <li data-show-field="idUrl{{ $i }}">
                    {{-- A picture opens large in the viewer below, right on this page --}}
                    <button type="button" class="id-proof" data-view-field="idImg{{ $i }}" data-show-field="idImg{{ $i }}" data-id-title="ID photo {{ $i + 1 }}" aria-label="View ID photo {{ $i + 1 }} larger">
                        <img data-src-field="idImg{{ $i }}" alt="Uploaded ID photo {{ $i + 1 }}">
                    </button>
                    {{-- A PDF cannot show as a picture, so it opens in its own tab --}}
                    <a class="id-proof" href="#" data-href-field="idUrl{{ $i }}" data-show-field="idPdf{{ $i }}" target="_blank" rel="noopener" aria-label="Open PDF {{ $i + 1 }} in a new tab">
                        <span class="id-proof-file"><x-admin.icon name="file-text" /> PDF document</span>
                    </a>
                </li>
            @endfor
        </ul>
        <p class="dlg-text dlg-text--center" data-show-field="noId">No ID uploaded.</p>
    </section>

    <section>
        <h3 class="dlg-label">Message and preferred schedule</h3>
        <p class="dlg-text" data-field="message"></p>
    </section>

    <form method="POST" class="dlg-status" data-action-field="updateUrl" data-pressed-field="statusValue">
        @csrf
        @method('PATCH')
        <span class="dlg-status-label" id="training-status-label">Set status</span>
        <div class="dlg-status-buttons" role="group" aria-labelledby="training-status-label">
            <button type="submit" name="status" value="pending" class="btn btn-secondary btn-sm">Pending</button>
            <button type="submit" name="status" value="contacted" class="btn btn-secondary btn-sm">Contacted</button>
            <button type="submit" name="status" value="completed" class="btn btn-secondary btn-sm">Completed</button>
            <button type="submit" name="status" value="cancelled" class="btn btn-secondary btn-sm"
                    data-confirm-title="Cancel this training request?"
                    data-confirm-text="The request will be marked cancelled. You can change the status again later."
                    data-confirm-ok="Mark cancelled">Cancelled</button>
        </div>
    </form>

    <x-slot:foot>
        <form method="POST" class="dlg-foot-danger" data-action-field="deleteUrl" data-confirm-field="deleteText"
              data-confirm-title="Delete this training request?" data-confirm-ok="Delete request">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Delete</button>
        </form>
        <div class="dlg-foot-main">
            <button type="button" class="btn btn-secondary" data-dialog-close>Close</button>
            <a class="btn btn-primary" data-href-field="mailto"><x-admin.icon name="mail" /> Email requester</a>
        </div>
    </x-slot:foot>
</x-admin.dialog>

{{-- Photo viewer: opened by a click on an ID photo above (resources/js/admin/admin.js) --}}
<dialog class="dlg dlg-photo" id="idViewer" aria-labelledby="idViewerTitle">
    <div class="dlg-head">
        <div class="dlg-head-main">
            <p class="dlg-kicker">Training request · Identity verification</p>
            <h2 id="idViewerTitle" data-id-title>ID photo</h2>
        </div>
        <button type="button" class="icon-btn" data-dialog-close aria-label="Close"><x-admin.icon name="x" /></button>
    </div>
    <div class="picture-detail-media"><img data-id-img alt="Uploaded ID, large view"></div>
</dialog>
@endsection
