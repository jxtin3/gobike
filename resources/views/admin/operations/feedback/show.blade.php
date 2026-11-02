@extends('admin.operations.layout')
@section('title', $gobiker->name.' — feedback')
@section('heading', $gobiker->name)
@section('subheading', 'What residents said about this GoBiker, rated in the app after an emergency or a check-up.')
@section('back', route('admin.operations.feedback.index'))
@section('back_label', 'Back to feedback')

@section('content')
@php
    $tabs = ['all' => 'All', 'sos' => 'Emergency SOS', 'checkup' => 'Check-ups', 'skipped' => 'Skipped'];
    $searching = filled(request('search'));
    $average = $figures['average'] ? number_format($figures['average'], 1) : null;
@endphp

<p class="report-meta">
    <span><x-admin.icon name="map" /> {{ $gobiker->barangay ?: 'No barangay' }}</span>
    @if ($gobiker->mobile)
        <span><x-admin.icon name="phone-call" /> <a href="tel:{{ $gobiker->mobile }}">{{ $gobiker->mobile }}</a></span>
    @endif
    <span><x-admin.icon name="eye" /> <a href="{{ route('admin.operations.users.show', $gobiker) }}">View account</a></span>
</p>

<div class="stat-row">
    <div class="stat-card">
        <span class="stat-icon"><x-admin.icon name="star" /></span>
        <div>
            <div class="stat-num">{{ $average ?? '–' }}</div>
            <div class="stat-label">{{ $average ? 'Average of '.number_format($figures['rated']).' '.Str::plural('rating', $figures['rated']) : 'No ratings yet' }}</div>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon t-ok"><x-admin.icon name="users" /></span>
        <div>
            <div class="stat-num">{{ number_format($counts['all']) }}</div>
            <div class="stat-label">Feedback · {{ number_format($counts['sos']) }} emergency, {{ number_format($counts['checkup']) }} check-up</div>
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

<nav class="tabs" aria-label="Filter this GoBiker's feedback">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.operations.feedback.show', [$gobiker] + array_filter(['kind' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
           class="tab" aria-current="{{ $tab === $key ? 'page' : 'false' }}" data-skeleton="rows">
            {{ $label }} <span class="count">{{ number_format($counts[$key]) }}</span>
        </a>
    @endforeach
</nav>

<x-admin.filters :action="route('admin.operations.feedback.show', $gobiker)" placeholder="Search by resident or comment" label="Search this GoBiker's feedback"
                 :hidden="['kind' => $tab === 'all' ? null : $tab]" :filtered="$searching" />

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Resident</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>For</th>
                <th>Given</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ratings as $rating)
                @php $resident = $rating->resident?->name ?? 'Deleted resident'; @endphp
                <tr>
                    <td>
                        <div class="cell-user">
                            <x-admin.avatar :name="$resident" :src="$rating->resident?->photo_path ? \App\Support\Media::url($rating->resident->photo_path) : null" />
                            <div>
                                <b>{{ $resident }}</b>
                                <small>{{ $rating->resident?->barangay ?? 'No barangay' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if ($rating->was_skipped)
                            <span class="pill pill-neutral">Skipped</span>
                        @else
                            {{-- Stars for a glance, the number for anyone who cannot tell them apart. --}}
                            <span class="stars" role="img" aria-label="{{ $rating->rating }} out of 5 stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <x-admin.icon name="star" @class(['star', 'is-on' => $i <= $rating->rating]) />
                                @endfor
                            </span>
                            <small class="sub-line">{{ $rating->rating }}/5</small>
                        @endif
                    </td>
                    {{-- The whole comment, wrapped: this page is for reading what residents wrote. --}}
                    <td class="feedback-comment">
                        @if ($rating->comment)
                            {{ $rating->comment }}
                        @else
                            <span class="muted">No comment</span>
                        @endif
                    </td>
                    <td>{{ $rating->kind_label }}</td>
                    <td>
                        {{ $rating->created_at->format('M j, Y') }}
                        <small class="sub-line">{{ $rating->created_at->diffForHumans() }}</small>
                    </td>
                    <td>
                        <div class="row-actions">
                            {{-- Not offered when the emergency or check-up has since been deleted. --}}
                            @if ($links[$rating->id] ?? '')
                                <a class="icon-btn" href="{{ $links[$rating->id] }}" title="Open the record" aria-label="Open the {{ strtolower($rating->kind_label) }} {{ $resident }} rated"><x-admin.icon name="eye" /></a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                @if ($searching)
                    <x-admin.empty :row="6" icon="star" title="No feedback matches these filters" :clear="route('admin.operations.feedback.show', $gobiker)">
                        Try a different name or word, or clear the filters.
                    </x-admin.empty>
                @elseif ($tab === 'skipped')
                    <x-admin.empty :row="6" icon="star" title="Nobody has skipped a rating">
                        Residents who close the rating without answering appear here.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="6" icon="star" title="No feedback for this GoBiker yet">
                        After {{ $gobiker->name }} resolves an emergency or records a check-up, the resident is asked to rate them.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$ratings" noun="rating" />
</div>
@endsection
