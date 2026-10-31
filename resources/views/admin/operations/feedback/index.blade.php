@extends('admin.operations.layout')
@section('title', 'Feedback')
@section('heading', 'Feedback')
@section('subheading', 'What residents thought of the GoBiker who helped them, rated in the app after an emergency or a check-up.')

@section('content')
@php
    $tabs = ['all' => 'All', 'sos' => 'Emergency SOS', 'checkup' => 'Check-ups', 'skipped' => 'Skipped'];
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

<x-admin.filters :action="route('admin.operations.feedback.index')" placeholder="Search by resident, GoBiker or comment" label="Search feedback"
                 :hidden="['kind' => $tab === 'all' ? null : $tab]" :filtered="$searching" />

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>GoBiker</th>
                <th>Rating</th>
                <th>Resident</th>
                <th>Comment</th>
                <th>For</th>
                <th>Given</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ratings as $rating)
                @php
                    $gobiker = $rating->gobiker?->name ?? 'Deleted GoBiker';
                    $resident = $rating->resident?->name ?? 'Deleted resident';
                @endphp
                <tr class="is-clickable"
                    data-dialog="#feedbackDialog"
                    data-name="{{ $gobiker }}"
                    data-stars="{{ $rating->was_skipped ? 'Skipped' : $rating->rating.' out of 5' }}"
                    data-status="{{ $rating->was_skipped ? 'Skipped' : $rating->rating.'/5' }}"
                    data-status-pill="{{ $rating->rating_pill }}"
                    data-resident="{{ $resident }}"
                    data-barangay="{{ $rating->resident?->barangay ?? 'Unknown' }}"
                    data-kind="{{ $rating->kind_label }}"
                    data-comment="{{ $rating->comment ?: 'No comment' }}"
                    data-date="{{ $rating->created_at->format('M j, Y g:i A') }}"
                    @if ($links[$rating->id] ?? '') data-subject-url="{{ $links[$rating->id] }}" @endif>
                    <td>
                        <div class="cell-user">
                            <x-admin.avatar :name="$gobiker" :src="$rating->gobiker?->photo_path ? \App\Support\Media::url($rating->gobiker->photo_path) : null" />
                            <div>
                                <b>{{ $gobiker }}</b>
                                <small>{{ $rating->gobiker?->barangay ?? 'No barangay' }}</small>
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
                    <td>
                        {{ $resident }}
                        <small class="sub-line">{{ $rating->resident?->barangay ?? 'No barangay' }}</small>
                    </td>
                    <td title="{{ $rating->comment }}">
                        @if ($rating->comment)
                            {{ Str::limit($rating->comment, 60) }}
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
                            <button type="button" class="icon-btn" data-open title="Open" aria-label="Open the feedback for {{ $gobiker }}"><x-admin.icon name="eye" /></button>
                        </div>
                    </td>
                </tr>
            @empty
                @if ($searching)
                    <x-admin.empty :row="7" icon="star" title="No feedback matches these filters" :clear="route('admin.operations.feedback.index')">
                        Try a different name or word, or clear the filters.
                    </x-admin.empty>
                @elseif ($tab === 'skipped')
                    <x-admin.empty :row="7" icon="star" title="Nobody has skipped a rating">
                        Residents who close the rating without answering appear here.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="7" icon="star" title="No feedback yet">
                        After a GoBiker resolves an emergency or records a check-up, the resident is asked to rate them. Their answers appear here.
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$ratings" noun="rating" />
</div>

<x-admin.dialog id="feedbackDialog" kicker="Feedback for" size="sm">
    <x-slot:sub><span data-pill-field="status"></span></x-slot:sub>

    <dl class="dlg-meta">
        <div><dt>Rating</dt><dd data-field="stars"></dd></div>
        <div><dt>For</dt><dd data-field="kind"></dd></div>
        <div><dt>Resident</dt><dd data-field="resident"></dd></div>
        <div><dt>Barangay</dt><dd data-field="barangay"></dd></div>
        <div class="dlg-meta-wide"><dt>Given</dt><dd data-field="date"></dd></div>
    </dl>

    <section>
        <h3 class="dlg-label">Comment</h3>
        <p class="dlg-text" data-field="comment"></p>
    </section>

    <x-slot:foot>
        <div class="dlg-foot-main">
            {{-- Hidden when the emergency or check-up has since been deleted. --}}
            <a class="btn btn-ghost" data-href-field="subjectUrl" data-show-field="subjectUrl">Open the record</a>
            <button type="button" class="btn" data-dialog-close>Close</button>
        </div>
    </x-slot:foot>
</x-admin.dialog>
@endsection
