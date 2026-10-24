@extends('admin.operations.layout')
@section('title', 'Resident SOS')
@section('heading', 'Resident SOS')
@section('subheading', 'Emergency requests residents send with the SOS button in the RondaGo app.')

@section('content')
@php
    $tabs = ['open' => 'Open', 'sent' => 'Waiting', 'responding' => 'Responding', 'resolved' => 'Resolved', 'all' => 'All'];
    $pills = ['sent' => 'bad', 'responding' => 'warn', 'resolved' => 'ok'];
    $searching = filled(request('search'));
@endphp

<nav class="tabs" aria-label="Filter SOS requests">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.sos.index', array_filter(['status' => $key === 'open' ? null : $key, 'search' => request('search')])) }}"
           class="tab" aria-current="{{ $tab === $key ? 'page' : 'false' }}" data-skeleton="rows">
            {{ $label }} <span class="count">{{ number_format($counts[$key]) }}</span>
        </a>
    @endforeach
</nav>

<x-admin.filters :action="route('admin.operations.sos.index')" placeholder="Search by resident, barangay or landmark" label="Search SOS requests"
                 :hidden="['status' => $tab === 'open' ? null : $tab]" :filtered="$searching" />

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Resident</th>
                <th>Where</th>
                <th>Sent</th>
                <th>Status</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $sos)
                @php $who = $sos->user?->name ?? 'Deleted resident'; @endphp
                <tr @class(['is-unread' => $sos->status === 'sent'])>
                    <td>
                        <a href="{{ route('admin.operations.sos.show', $sos) }}"><b>{{ $who }}</b></a>
                        <small class="sub-line">{{ $sos->user?->mobile ?: 'No mobile number' }}</small>
                    </td>
                    <td>
                        {{ $sos->barangay }}, {{ $sos->municipality }}
                        <small class="sub-line">{{ Str::limit($sos->landmark, 60) }}</small>
                    </td>
                    <td>
                        {{ $sos->created_at->format('M j, Y') }}
                        <small class="sub-line">{{ $sos->created_at->format('g:i A') }} · {{ $sos->created_at->diffForHumans() }}</small>
                    </td>
                    <td>
                        <span class="pill pill-{{ $pills[$sos->status] ?? 'neutral' }}">{{ $sos->status_label }}</span>
                        @if ($sos->image_path || $sos->video_path)
                            <small class="sub-line">
                                @if ($sos->image_path)<x-admin.icon name="image" /> Photo @endif
                                @if ($sos->video_path)<x-admin.icon name="film" /> Video @endif
                            </small>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            @if ($sos->user?->mobile)
                                <a class="icon-btn" href="tel:{{ $sos->user->mobile }}" title="Call" aria-label="Call {{ $who }}"><x-admin.icon name="phone-call" /></a>
                            @endif
                            <a class="icon-btn" href="{{ route('admin.operations.sos.show', $sos) }}" title="Open" aria-label="Open SOS request from {{ $who }}"><x-admin.icon name="eye" /></a>
                        </div>
                    </td>
                </tr>
            @empty
                @if ($searching)
                    <x-admin.empty :row="5" icon="alert-triangle" title="No requests match these filters" :clear="route('admin.operations.sos.index')">
                        Try a different name or place, or clear the filters.
                    </x-admin.empty>
                @elseif (in_array($tab, ['open', 'sent'], true))
                    <x-admin.empty :row="5" icon="check-circle" title="No open SOS requests">
                        New requests ring the alarm on every admin page and appear here.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="5" icon="alert-triangle" title="Nothing here yet">
                        Requests with this status will appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$requests" noun="request" />
</div>
@endsection
