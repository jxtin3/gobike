@extends('admin.operations.layout')
@section('title', 'Feedback')
@section('heading', 'Feedback')
@section('subheading', 'What residents thought of the GoBiker who helped them, rated in the app after an emergency or a check-up. Open a GoBiker to read what each resident said.')

@section('content')
@php
    $tabs = ['all' => 'All', 'sos' => 'Emergency SOS', 'checkup' => 'Check-ups'];
    $searching = filled(request('search'));
    $average = $figures['average'] ? number_format($figures['average'], 1) : null;
@endphp

<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="star" /></span>
        <div>
            <div class="stat-num">{{ $average ?? '–' }}</div>
            <div class="stat-label">{{ $average ? 'Average of '.number_format($figures['rated']).' ratings' : 'No ratings yet' }}</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon t-ok"><x-admin.icon name="users" /></span>
        <div>
            <div class="stat-num">{{ number_format($figures['gobikers']) }}</div>
            <div class="stat-label">GoBikers rated</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon t-off"><x-admin.icon name="bell-off" /></span>
        <div>
            <div class="stat-num">{{ number_format($figures['skipped']) }}</div>
            <div class="stat-label">Skipped by the resident</div>
        </div>
    </div>
</div>

<nav class="tabs" aria-label="Filter feedback">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.feedback.index', array_filter(['kind' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
           class="tab" aria-current="{{ $tab === $key ? 'page' : 'false' }}" data-skeleton="rows">
            {{ $label }} <span class="count">{{ number_format($counts[$key]) }}</span>
        </a>
    @endforeach
</nav>

<x-admin.filters :action="route('admin.operations.feedback.index')" placeholder="Search by GoBiker or barangay" label="Search GoBikers"
                 :hidden="['kind' => $tab === 'all' ? null : $tab]" :filtered="$searching" />

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>GoBiker</th>
                <th>Average rating</th>
                <th>Ratings</th>
                <th>Last rated</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gobikers as $row)
                @php
                    $user = $users->get($row->gobiker_id);
                    $name = $user?->name ?? 'Deleted GoBiker';
                    $rated = (int) $row->rated;
                    $skipped = (int) $row->skipped;
                    $mean = $rated ? (float) $row->average : null;
                    $lastRated = \Illuminate\Support\Carbon::parse($row->last_at);
                    $url = $user ? route('admin.operations.feedback.show', $user) : null;
                @endphp
                {{-- The whole row opens the GoBiker's page; the name is a real link for keyboards and screen readers. --}}
                <tr @if ($url) data-href="{{ $url }}" @endif>
                    <td>
                        <div class="cell-user">
                            <x-admin.avatar :name="$name" :src="$user?->photo_path ? \App\Support\Media::url($user->photo_path) : null" />
                            <div>
                                @if ($url)
                                    <a href="{{ $url }}"><b>{{ $name }}</b></a>
                                @else
                                    <b>{{ $name }}</b>
                                @endif
                                <small>{{ $user?->barangay ?? 'No barangay' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if ($mean === null)
                            <span class="pill pill-neutral">Skipped only</span>
                        @else
                            {{-- Stars for a glance, the number for anyone who cannot tell them apart. --}}
                            <span class="stars" role="img" aria-label="Average {{ number_format($mean, 1) }} out of 5 stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <x-admin.icon name="star" @class(['star', 'is-on' => $i <= round($mean)]) />
                                @endfor
                            </span>
                            <small class="sub-line">{{ number_format($mean, 1) }}/5</small>
                        @endif
                    </td>
                    <td>
                        {{ number_format($rated) }} {{ Str::plural('rating', $rated) }}
                        @if ($skipped)
                            <small class="sub-line">{{ number_format($skipped) }} skipped</small>
                        @endif
                    </td>
                    <td>
                        {{ $lastRated->format('M j, Y') }}
                        <small class="sub-line">{{ $lastRated->diffForHumans() }}</small>
                    </td>
                    <td>
                        <div class="row-actions">
                            @if ($url)
                                <a class="icon-btn" href="{{ $url }}" title="Open" aria-label="Open the feedback for {{ $name }}"><x-admin.icon name="eye" /></a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                @if ($searching)
                    <x-admin.empty :row="5" icon="star" title="No GoBiker matches these filters" :clear="route('admin.operations.feedback.index')">
                        Try a different name or barangay, or clear the filters.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="5" icon="star" title="No feedback yet">
                        After a GoBiker resolves an emergency or records a check-up, the resident is asked to rate them. Their answers appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$gobikers" noun="GoBiker" />
</div>
@endsection
