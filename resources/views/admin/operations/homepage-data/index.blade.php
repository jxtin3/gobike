@extends('admin.operations.layout')
@section('title', 'Homepage content')
@section('heading', 'Homepage content')
@section('subheading', 'Manage the impact statistics shown on the public homepage.')

@section('content')
<div class="homepage-data-grid">
    @foreach ([
        ['title' => 'Homepage impact stats', 'section' => 'impact', 'stats' => $impactStats],
        ['title' => 'Featured impact highlights', 'section' => 'highlight', 'stats' => $impactHighlights],
        ['title' => 'Featured impact badge', 'section' => 'badge', 'stats' => $impactBadge],
    ] as $group)
        <section class="homepage-data-section">
            <header class="homepage-data-heading">
                <div>
                    <h2>{{ $group['title'] }}</h2>
                    <p>Values and labels displayed in this homepage section.</p>
                </div>
                <span class="badge badge-soft">{{ $group['stats']->count() }}</span>
            </header>

            <form class="homepage-data-create" method="POST" action="{{ route('admin.operations.homepage-data.impact-stats.store') }}">
                @csrf
                <input type="hidden" name="section" value="{{ $group['section'] }}">
                <label>
                    <span>Statistic label</span>
                    <input class="input" name="label" value="{{ old('label') }}" maxlength="100" required>
                </label>
                <label>
                    <span>Display value</span>
                    <input class="input" name="value" value="{{ old('value') }}" maxlength="40" placeholder="e.g. 2,500+" required>
                </label>
                <button class="btn btn-primary" type="submit">Add statistic</button>
            </form>

            <div class="homepage-data-list">
                @forelse ($group['stats'] as $stat)
                    <form class="homepage-data-item" method="POST" action="{{ route('admin.operations.homepage-data.impact-stats.update', $stat) }}">
                        @csrf
                        @method('PUT')
                        <label>
                            <span>Statistic label</span>
                            <input class="input" name="label" value="{{ $stat->label }}" maxlength="100" required>
                        </label>
                        <label>
                            <span>Display value</span>
                            <input class="input" name="value" value="{{ $stat->value }}" maxlength="40" required>
                        </label>
                        <div class="homepage-data-actions">
                            <button class="btn btn-secondary btn-sm" type="submit">Save</button>
                            <button class="icon-btn danger" type="submit" form="delete-stat-{{ $stat->id }}" aria-label="Delete {{ $stat->label }}" title="Delete statistic">
                                <x-admin.icon name="trash" />
                            </button>
                        </div>
                    </form>
                    <form id="delete-stat-{{ $stat->id }}" method="POST" action="{{ route('admin.operations.homepage-data.impact-stats.destroy', $stat) }}"
                          data-confirm-title="Remove this statistic?"
                          data-confirm-text="{{ $stat->label }} will no longer appear on the homepage."
                          data-confirm-ok="Remove statistic">
                        @csrf
                        @method('DELETE')
                    </form>
                @empty
                    <p class="homepage-data-empty">No statistics in this section yet.</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
@endsection
