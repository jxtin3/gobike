@extends('admin.operations.layout')
@section('title', 'Appointments')
@section('heading', 'Appointments')
@section('subheading', 'Home check-up visits residents request in the RondaGo app. GoBikers in the same barangay can accept them in the app.')

@section('content')
@php
    $tabs = ['pending' => 'Waiting', 'accepted' => 'Assigned', 'completed' => 'Done', 'closed' => 'Declined / cancelled', 'all' => 'All'];
    $pills = ['pending' => 'warn', 'accepted' => 'info', 'completed' => 'ok', 'declined' => 'neutral', 'cancelled' => 'neutral'];
    $searching = filled(request('search'));
@endphp

<nav class="tabs" aria-label="Filter appointments">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.appointments.index', array_filter(['status' => $key === 'pending' ? null : $key, 'search' => request('search')])) }}"
           class="tab" aria-current="{{ $tab === $key ? 'page' : 'false' }}" data-skeleton="rows">
            {{ $label }} <span class="count">{{ number_format($counts[$key]) }}</span>
        </a>
    @endforeach
</nav>

<x-admin.filters :action="route('admin.operations.appointments.index')" placeholder="Search by resident or barangay" label="Search appointments"
                 :hidden="['status' => $tab === 'pending' ? null : $tab]" :filtered="$searching" />

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Resident</th>
                <th>Preferred visit</th>
                <th>Reason</th>
                <th>Status</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appointments as $a)
                @php $who = $a->resident?->name ?? 'Deleted resident'; @endphp
                <tr data-href="{{ route('admin.operations.appointments.show', $a) }}">
                    <td>
                        <a href="{{ route('admin.operations.appointments.show', $a) }}"><b>{{ $who }}</b></a>
                        <small class="sub-line">{{ $a->barangay }}</small>
                    </td>
                    <td>
                        {{ $a->preferred_date->format('D, M j') }}
                        <small class="sub-line">{{ ucfirst($a->preferred_window) }}</small>
                    </td>
                    <td class="cell-preview">{{ $a->reason }}</td>
                    <td>
                        <span class="pill pill-{{ $pills[$a->status] ?? 'neutral' }}">{{ $a->status_label }}</span>
                        @if ($a->gobiker)
                            <small class="sub-line">{{ $a->gobiker->name }}</small>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.operations.appointments.show', $a) }}" title="Open" aria-label="Open visit request from {{ $who }}"><x-admin.icon name="eye" /></a>
                        </div>
                    </td>
                </tr>
            @empty
                @if ($searching)
                    <x-admin.empty :row="5" icon="clipboard" title="No requests match these filters" :clear="route('admin.operations.appointments.index')">
                        Try a different name or barangay, or clear the filters.
                    </x-admin.empty>
                @elseif ($tab === 'pending')
                    <x-admin.empty :row="5" icon="check-circle" title="No visits waiting">
                        Requests residents send from the app appear here until a GoBiker takes them.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="5" icon="clipboard" title="Nothing here yet">
                        Requests with this status will appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$appointments" noun="request" />
</div>
@endsection
