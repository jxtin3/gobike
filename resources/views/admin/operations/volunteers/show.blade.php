@extends('admin.operations.layout')
@section('title', $application->first_name . ' ' . $application->last_name . ' — Volunteer Application')
@section('heading', $application->first_name . ' ' . $application->last_name)
@section('subheading', 'Volunteer application detail')
@section('back', route('admin.operations.volunteers.index'))
@section('back_label', 'Back to Applications')

@section('content')
@php
    $statusColors = [
        'pending'    => 'warn',
        'approved'   => 'ok',
        'rejected'   => 'bad',
        'waitlisted' => 'neutral',
    ];
    $statusColor = $statusColors[$application->application_status] ?? 'neutral';
@endphp

<div class="detail-grid">

    {{-- Left: main info --}}
    <div class="detail-main">

        {{-- Personal Info --}}
        <section class="detail-card">
            <h2 class="detail-card-title">Personal Information</h2>
            <dl class="detail-dl">
                <div><dt>Full Name</dt><dd>{{ $application->first_name }} {{ $application->last_name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $application->email }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $application->phone }}</dd></div>
                <div><dt>Age</dt><dd>{{ $application->age }} years old</dd></div>
                <div><dt>Occupation Status</dt><dd>{{ ucfirst(str_replace('-', ' ', $application->occupation_status)) }}</dd></div>
                @if($application->school)
                    <div><dt>School / Institution</dt><dd>{{ $application->school }}</dd></div>
                @endif
            </dl>
        </section>

        {{-- Location --}}
        <section class="detail-card">
            <h2 class="detail-card-title">Location</h2>
            <dl class="detail-dl">
                <div><dt>Barangay</dt><dd>{{ $application->barangay }}</dd></div>
                <div><dt>Municipality</dt><dd>{{ $application->municipality }}</dd></div>
                <div><dt>Province</dt><dd>{{ $application->province }}</dd></div>
            </dl>
        </section>

        {{-- Volunteer Details --}}
        <section class="detail-card">
            <h2 class="detail-card-title">Volunteer Details</h2>
            <dl class="detail-dl">
                <div><dt>Availability</dt><dd>{{ ucfirst($application->availability) }}</dd></div>
                <div><dt>Has a bike</dt><dd>{{ $application->has_bike ? 'Yes' : 'No' }}</dd></div>
            </dl>
            @if($application->motivation)
                <div class="detail-note">
                    <span class="detail-note-label">Motivation</span>
                    <p>{{ $application->motivation }}</p>
                </div>
            @endif
        </section>

        {{-- Emergency Contact --}}
        <section class="detail-card">
            <h2 class="detail-card-title">Emergency Contact</h2>
            <dl class="detail-dl">
                <div><dt>Name</dt><dd>{{ $application->emergency_name }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $application->emergency_phone }}</dd></div>
            </dl>
        </section>

        @if($application->guardian_name)
        {{-- Guardian Info (minors) --}}
        <section class="detail-card">
            <h2 class="detail-card-title">Guardian / Parent</h2>
            <dl class="detail-dl">
                <div><dt>Name</dt><dd>{{ $application->guardian_name }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $application->guardian_phone }}</dd></div>
                <div><dt>Consent given</dt><dd>{{ $application->guardian_consent ? 'Yes' : 'No' }}</dd></div>
            </dl>
        </section>
        @endif

    </div>

    {{-- Right: status + meta --}}
    <div class="detail-aside">
        <section class="detail-card">
            <h2 class="detail-card-title">Application Status</h2>
            <div style="margin-bottom:16px">
                <span class="pill pill-{{ $statusColor }}" style="font-size:13px;padding:6px 14px">{{ ucfirst($application->application_status) }}</span>
            </div>
            <form method="POST" action="{{ route('admin.operations.volunteers.status', $application) }}" class="status-form">
                @csrf
                @method('PATCH')
                <label class="input-label">Update Status</label>
                <select name="application_status" class="input" style="width:100%;margin-bottom:10px" onchange="this.form.submit()">
                    <option value="pending"    @selected($application->application_status === 'pending')>Pending</option>
                    <option value="approved"   @selected($application->application_status === 'approved')>Approved</option>
                    <option value="waitlisted" @selected($application->application_status === 'waitlisted')>Waitlisted</option>
                    <option value="rejected"   @selected($application->application_status === 'rejected')>Rejected</option>
                </select>
            </form>
        </section>

        <section class="detail-card">
            <h2 class="detail-card-title">Submitted</h2>
            <p style="font-size:14px;color:var(--ink-2)">{{ $application->created_at->format('F j, Y') }}</p>
            <p style="font-size:13px;color:var(--ink-3)">{{ $application->created_at->format('g:i A') }}</p>
        </section>

        <div style="margin-top:4px">
            <a class="btn btn-secondary" style="width:100%;justify-content:center" href="mailto:{{ $application->email }}">
                <x-admin.icon name="mail" /> Email Applicant
            </a>
        </div>
    </div>

</div>
@endsection
