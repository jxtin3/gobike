@extends('admin.operations.layout')
@section('title', $patient->name)
@section('heading', $patient->name)
@section('subheading', 'Check-up recorded '.$patient->recorded_at->format('F j, Y \a\t g:i A').' · '.$history->count().' '.Str::plural('check-up', $history->count()).' in total')
@section('back', route('admin.operations.patients.index'))
@section('back_label', 'Back to patients')
@section('actions')
    <button type="button" class="btn btn-secondary" data-print><x-admin.icon name="printer" /> Print</button>
    <a class="btn btn-secondary" href="{{ route('admin.operations.patients.show', [$patient, 'format' => 'csv']) }}"><x-admin.icon name="download" /> Download CSV</a>
@endsection

@section('content')
@php
    $fmt = fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
    $needed = \App\Models\Patient::NORMAL_CHECKUPS_TO_STOP;
@endphp

@include('admin.partials.print-head', ['title' => 'Patient record: '.$patient->name, 'subtitle' => $history->count().' '.Str::plural('check-up', $history->count())])

{{-- Follow-up: should GoBikers keep checking this person? --}}
<section class="follow-up" aria-labelledby="follow-up-title">
    <div class="follow-up-head">
        <h2 id="follow-up-title">Follow-up</h2>
        @if ($followUp)
            <span class="pill {{ $followUp->decision === 'stop' ? 'pill-ok' : 'pill-info' }}">{{ $followUp->label }}</span>
        @else
            <span class="pill pill-neutral">No decision yet</span>
        @endif
    </div>

    @php $dates = $suggestion['checkups']->map(fn ($c) => $c->recorded_at->format('M j'))->implode(', '); @endphp
    @if ($suggestion['state'] === 'can_stop')
        <p class="follow-up-hint is-ok"><x-admin.icon name="check-circle" /> <span><b>Normal on the last {{ $needed }} check-ups</b> ({{ $dates }}). You can tell the GoBiker to stop regular check-ups.</span></p>
    @elseif ($suggestion['state'] === 'keep')
        @php $flagged = $suggestion['checkups']->first(fn ($c) => ! $c->isNormal()); @endphp
        <p class="follow-up-hint is-warn"><x-admin.icon name="alert-triangle" /> <span><b>Keep checking:</b> {{ implode(', ', $flagged->abnormalReadings()) }} on {{ $flagged->recorded_at->format('M j, Y') }}.</span></p>
    @else
        <p class="follow-up-hint"><x-admin.icon name="clock" /> <span><b>Not enough check-ups yet</b> ({{ $suggestion['checkups']->count() }} of {{ $needed }}). The system suggests stopping after {{ $needed }} normal check-ups in a row.</span></p>
    @endif

    @if ($followUp)
        <p class="follow-up-decided">
            {{ $followUp->label }} · decided {{ $followUp->decided_at->format('M j, Y') }}{{ $followUp->decider ? ' by '.$followUp->decider->name : '' }}
            @if ($followUp->note)<br><span class="muted">“{{ $followUp->note }}”</span>@endif
        </p>
    @endif

    <form method="POST" action="{{ route('admin.operations.patients.follow-up', $patient) }}" class="follow-up-form no-print">
        @csrf
        @method('PATCH')
        <label for="follow-up-note">Note for the GoBiker <span class="muted">(optional)</span></label>
        <textarea @class(['input', 'is-invalid' => $errors->has('note')]) id="follow-up-note" name="note" rows="2" maxlength="500" aria-describedby="follow-up-hint note-error" placeholder="e.g. Blood pressure normal since July. Check again only if they feel unwell.">{{ old('note', $followUp?->note) }}</textarea>
        <x-admin.field-error name="note" />
        <p class="hint" id="follow-up-hint">The GoBiker app will show this decision for every check-up of {{ $patient->name }}.</p>
        <div class="follow-up-actions">
            <button type="submit" name="decision" value="continue" class="btn btn-secondary" aria-pressed="{{ $followUp?->decision === 'continue' ? 'true' : 'false' }}">
                <x-admin.icon name="activity" /> Keep checking
            </button>
            <button type="submit" name="decision" value="stop" class="btn btn-secondary" aria-pressed="{{ $followUp?->decision === 'stop' ? 'true' : 'false' }}"
                    data-confirm-title="Stop regular check-ups for {{ $patient->name }}?"
                    data-confirm-text="GoBikers will be told they no longer need to check this person every ronda. You can change this later."
                    data-confirm-ok="Stop checking" data-confirm-tone="primary">
                <x-admin.icon name="check" /> Stop checking
            </button>
        </div>
    </form>
</section>

<div class="detail-columns">
    <article class="detail-card">
        <h2 class="detail-card-title">Personal details</h2>
        <dl class="detail-dl">
            <div><dt>Full name</dt><dd>{{ $patient->name }}</dd></div>
            <div><dt>Age</dt><dd>{{ $patient->age }} yrs</dd></div>
            <div><dt>Address</dt><dd>{{ $patient->address }}</dd></div>
            <div><dt>Contact</dt><dd>@if ($patient->contact)<a href="tel:{{ $patient->contact }}">{{ $patient->contact }}</a>@else — @endif</dd></div>
            <div><dt>Barangay</dt><dd>{{ $patient->barangay ?: '—' }}</dd></div>
        </dl>
    </article>

    <article class="detail-card">
        <h2 class="detail-card-title">This check-up · {{ $patient->recorded_at->format('M j, Y') }}</h2>
        <dl class="detail-dl">
            <div><dt>Blood pressure</dt><dd>{{ $patient->sys }}/{{ $patient->dia }} mmHg</dd></div>
            <div><dt>Pulse</dt><dd>{{ $patient->pulse }} bpm</dd></div>
            <div><dt>Respiration</dt><dd>{{ $patient->resp }} per minute</dd></div>
            <div><dt>Temperature</dt><dd>{{ $fmt($patient->temp) }} °C</dd></div>
            <div><dt>Blood sugar</dt><dd>{{ $patient->blood_sugar !== null ? $patient->blood_sugar.' mg/dL' : 'Not tested' }}</dd></div>
            <div><dt>Recorded by</dt><dd>{{ $patient->recorder?->name ?? '—' }}</dd></div>
        </dl>
    </article>
</div>

<section class="report-section">
    <h2>All check-ups <span class="muted">({{ $history->count() }})</span></h2>
    <div class="table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>BP</th>
                    <th class="num">Pulse</th>
                    <th class="num">Resp.</th>
                    <th class="num">Temp</th>
                    <th class="num">Sugar</th>
                    <th>Readings</th>
                    <th>Recorded by</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($history as $checkup)
                    <tr @class(['is-static', 'is-current' => $checkup->is($patient)])>
                        <td>
                            @if ($checkup->is($patient))
                                <b>{{ $checkup->recorded_at->format('M j, Y · g:i A') }}</b> <span class="muted">(this one)</span>
                            @else
                                <a class="cell-link" href="{{ route('admin.operations.patients.show', $checkup) }}" data-skeleton="detail">{{ $checkup->recorded_at->format('M j, Y · g:i A') }}</a>
                            @endif
                        </td>
                        <td>{{ $checkup->sys }}/{{ $checkup->dia }}</td>
                        <td class="num">{{ $checkup->pulse }}</td>
                        <td class="num">{{ $checkup->resp }}</td>
                        <td class="num">{{ $fmt($checkup->temp) }} °C</td>
                        <td class="num">{{ $checkup->blood_sugar !== null ? $checkup->blood_sugar : '—' }}</td>
                        <td>
                            @forelse ($checkup->abnormalReadings() as $key => $label)
                                <span class="pill {{ str_ends_with($key, '_high') ? 'pill-warn' : 'pill-info' }}">{{ $label }}</span>
                            @empty
                                <span class="pill pill-ok">Normal</span>
                            @endforelse
                        </td>
                        <td>{{ $checkup->recorder?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="report-footnote">Check-ups are matched to this person by name and contact number. High and low flags are screening hints, not a diagnosis.</p>
</section>

<div class="detail-danger no-print">
    <p>Deleting removes only this check-up. The patient's other check-ups are kept.</p>
    <form method="POST" action="{{ route('admin.operations.patients.destroy', $patient) }}"
          data-confirm-title="Delete this check-up?"
          data-confirm-text="This permanently deletes the check-up from {{ $patient->recorded_at->format('M j, Y') }}. Other check-ups of {{ $patient->name }} are kept."
          data-confirm-ok="Delete check-up">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Delete check-up</button>
    </form>
</div>
@endsection
