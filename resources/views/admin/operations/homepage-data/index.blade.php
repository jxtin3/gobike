@extends('admin.operations.layout')
@section('title', 'Homepage content')
@section('heading', 'Homepage content')
@section('subheading', 'Manage the impact statistics shown on the public homepage.')

@section('content')
<div class="homepage-data-grid">
    @foreach ([
        ['title' => 'Homepage impact stats', 'description' => 'The row of numbers near the top of the homepage.', 'section' => 'impact', 'stats' => $impactStats],
        ['title' => 'Featured impact highlights', 'description' => 'The highlight figures in the featured impact section.', 'section' => 'highlight', 'stats' => $impactHighlights],
        ['title' => 'Featured impact badge', 'description' => 'The badge shown on the featured impact image.', 'section' => 'badge', 'stats' => $impactBadge],
    ] as $group)
        @php
            $createKey = 'create_'.$group['section'];
            $createOld = old('form_key') === $createKey;
            $createErrors = $errors->getBag($createKey);
        @endphp
        <section class="homepage-data-section" aria-labelledby="section-{{ $group['section'] }}">
            <header class="homepage-data-heading">
                <div>
                    <h2 id="section-{{ $group['section'] }}">{{ $group['title'] }}</h2>
                    <p>{{ $group['description'] }}</p>
                </div>
                <span class="badge badge-soft" title="Statistics in this section">{{ $group['stats']->count() }}</span>
            </header>

            <form class="homepage-data-create" method="POST" action="{{ route('admin.operations.homepage-data.impact-stats.store') }}">
                @csrf
                <input type="hidden" name="section" value="{{ $group['section'] }}">
                <input type="hidden" name="form_key" value="{{ $createKey }}">
                <label>
                    <span>Statistic label</span>
                    <input @class(['input', 'is-invalid' => $createErrors->has('label')]) name="label" value="{{ $createOld ? old('label') : '' }}" maxlength="100" required>
                    <x-admin.field-error name="label" :bag="$createKey" />
                </label>
                <label>
                    <span>Display value</span>
                    <input @class(['input', 'is-invalid' => $createErrors->has('value')]) name="value" value="{{ $createOld ? old('value') : '' }}" maxlength="40" placeholder="e.g. 2,500+" required>
                    <x-admin.field-error name="value" :bag="$createKey" />
                </label>
                <button class="btn btn-ok" type="submit"><x-admin.icon name="plus" /> Add statistic</button>
            </form>

            <div class="homepage-data-list">
                @forelse ($group['stats'] as $stat)
                    @php
                        $statKey = 'stat_'.$stat->id;
                        $statOld = old('form_key') === $statKey;
                        $statErrors = $errors->getBag($statKey);
                    @endphp
                    <form class="homepage-data-item" method="POST" action="{{ route('admin.operations.homepage-data.impact-stats.update', $stat) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="form_key" value="{{ $statKey }}">
                        <label>
                            <span>Statistic label</span>
                            <input @class(['input', 'is-invalid' => $statErrors->has('label')]) name="label" value="{{ $statOld ? old('label') : $stat->label }}" maxlength="100" required>
                            <x-admin.field-error name="label" :bag="$statKey" />
                        </label>
                        <label>
                            <span>Display value</span>
                            <input @class(['input', 'is-invalid' => $statErrors->has('value')]) name="value" value="{{ $statOld ? old('value') : $stat->value }}" maxlength="40" required>
                            <x-admin.field-error name="value" :bag="$statKey" />
                        </label>
                        <div class="homepage-data-actions">
                            <button class="btn btn-ok btn-sm" type="submit">Save</button>
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
