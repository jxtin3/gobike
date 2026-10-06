@extends('admin.operations.layout')
@section('title', 'Patient record')
@section('heading', $patient->name)
@section('subheading', 'Recorded ' . $patient->recorded_at->format('F j, Y \a\t g:i A'))
@section('back', route('admin.operations.patients.index'))
@section('back_label', 'Patients')
@section('actions')
    <form method="POST" action="{{ route('admin.operations.patients.destroy', $patient) }}"
          data-confirm-title="Delete this patient record?"
          data-confirm-text="This permanently deletes the record and cannot be undone."
          data-confirm-ok="Delete record">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-soft-danger">
            <x-admin.icon name="trash" /> Delete record
        </button>
    </form>
@endsection

@section('content')
@php
    $fmt = fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
@endphp

<article class="detail-panel">
    <h2 style="margin-top:0">Personal details</h2>
    <dl class="user-details">
        <dt>Full name</dt><dd>{{ $patient->name }}</dd>
        <dt>Age</dt><dd>{{ $patient->age }}</dd>
        <dt>Address</dt><dd>{{ $patient->address }}</dd>
        <dt>Contact</dt><dd>{{ $patient->contact }}</dd>
        <dt>Barangay</dt><dd>{{ $patient->barangay ?: '—' }}</dd>
        <dt>Recorded by</dt><dd>{{ $patient->recorder?->name ?? '—' }}</dd>
    </dl>
</article>

<article class="detail-panel" style="margin-top:16px">
    <h2 style="margin-top:0">Vital signs</h2>
    <dl class="user-details">
        <dt>Blood pressure</dt><dd>{{ $patient->sys }}/{{ $patient->dia }} mmHg</dd>
        <dt>Pulse</dt><dd>{{ $patient->pulse }} bpm</dd>
        <dt>Respiration</dt><dd>{{ $patient->resp }} per minute</dd>
        <dt>Temperature</dt><dd>{{ $fmt($patient->temp) }} °C</dd>
        <dt>Height</dt><dd>{{ $fmt($patient->height) }} cm</dd>
        <dt>Weight</dt><dd>{{ $fmt($patient->weight) }} kg</dd>
    </dl>
    <p class="muted" style="margin-bottom:0">
        "Elevated" flags on the list are screening hints only (BP 140/90 or higher, temperature 37.5 °C or higher), not a diagnosis.
    </p>
</article>
@endsection