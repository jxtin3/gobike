@extends('admin.operations.layout')
@php
    $who = $appointment->resident?->name ?? 'Deleted resident';
    $pills = ['pending' => 'warn', 'accepted' => 'info', 'completed' => 'ok', 'declined' => 'neutral', 'cancelled' => 'neutral'];
@endphp
@section('title', 'Visit for '.$who)
@section('heading', 'Visit for '.$who)
@section('subheading', 'Requested '.$appointment->created_at->format('F j, Y \a\t g:i A'))
@section('back', route('admin.operations.appointments.index'))
@section('back_label', 'Back to appointments')
@section('actions')
    @if ($appointment->resident?->mobile)
        <a class="btn btn-secondary" href="tel:{{ $appointment->resident->mobile }}"><x-admin.icon name="phone-call" /> Call {{ $appointment->resident->mobile }}</a>
    @endif
@endsection

@section('content')
<p class="detail-status"><span class="pill pill-{{ $pills[$appointment->status] ?? 'neutral' }}">{{ $appointment->status_label }}</span></p>

<div class="detail-columns">
    <article class="detail-card">
        <h2 class="detail-card-title">Visit</h2>
        <dl class="detail-dl">
            <div><dt>Preferred date</dt><dd>{{ $appointment->preferred_date->format('l, F j, Y') }}</dd></div>
            <div><dt>Time of day</dt><dd>{{ $appointment->window_label }}</dd></div>
            <div><dt>Address</dt><dd>{{ $appointment->address }}, {{ $appointment->barangay }}</dd></div>
            <div><dt>Reason</dt><dd>{!! nl2br(e($appointment->reason)) !!}</dd></div>
        </dl>
    </article>

    <article class="detail-card">
        <h2 class="detail-card-title">People</h2>
        <dl class="detail-dl">
            <div><dt>Resident</dt><dd>{{ $who }}</dd></div>
            <div><dt>Mobile</dt><dd>@if ($appointment->resident?->mobile)<a href="tel:{{ $appointment->resident->mobile }}">{{ $appointment->resident->mobile }}</a>@else — @endif</dd></div>
            <div><dt>GoBiker</dt><dd>{{ $appointment->gobiker?->name ?? 'Not assigned yet' }}</dd></div>
            @if ($appointment->patient)
                <div><dt>Check-up</dt><dd><a href="{{ route('admin.operations.patients.show', $appointment->patient) }}">View the check-up recorded on this visit</a></dd></div>
            @endif
            @if ($appointment->decline_note)
                <div><dt>Decline note</dt><dd>{{ $appointment->decline_note }}</dd></div>
            @endif
        </dl>
    </article>
</div>

@if ($appointment->isOpen())
    <div class="detail-columns no-print">
        <form class="detail-card" method="POST" action="{{ route('admin.operations.appointments.assign', $appointment) }}">
            @csrf
            @method('PATCH')
            <h2 class="detail-card-title">{{ $appointment->gobiker ? 'Reassign' : 'Assign a GoBiker' }}</h2>
            <div class="field">
                <label for="gobiker_id">GoBiker</label>
                <select class="input @error('gobiker_id') is-invalid @enderror" id="gobiker_id" name="gobiker_id" required aria-describedby="gobiker-hint gobiker_id-error">
                    <option value="">Choose a GoBiker…</option>
                    @foreach ($gobikers as $g)
                        <option value="{{ $g->id }}" @selected(old('gobiker_id', $appointment->gobiker_id) == $g->id)>
                            {{ $g->name }}{{ $g->barangay ? ' · '.$g->barangay : '' }}{{ $g->barangay === $appointment->barangay ? ' (same barangay)' : '' }}
                        </option>
                    @endforeach
                </select>
                <span class="hint" id="gobiker-hint">GoBikers in {{ $appointment->barangay }} are listed first.</span>
                <x-admin.field-error name="gobiker_id" />
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><x-admin.icon name="user-check" /> Assign</button>
            </div>
        </form>

        <form class="detail-card" method="POST" action="{{ route('admin.operations.appointments.decline', $appointment) }}"
              data-confirm-title="Decline this visit request?"
              data-confirm-text="{{ $who }} will see it as declined, with your note, in the app."
              data-confirm-ok="Decline request">
            @csrf
            @method('PATCH')
            <h2 class="detail-card-title">Decline</h2>
            <div class="field">
                <label for="decline_note">Note for the resident</label>
                <textarea class="input @error('decline_note') is-invalid @enderror" id="decline_note" name="decline_note" rows="3" maxlength="500" required aria-describedby="decline_note-error" placeholder="e.g. No GoBiker is available that day. Please visit the RHU or request another date.">{{ old('decline_note') }}</textarea>
                <x-admin.field-error name="decline_note" />
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-secondary"><x-admin.icon name="x" /> Decline</button>
            </div>
        </form>
    </div>
@endif
@endsection
