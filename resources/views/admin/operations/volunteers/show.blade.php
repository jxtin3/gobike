@extends('admin.operations.layout')
@section('title', $application->full_name.' — Volunteer application')
@section('heading', $application->full_name)
@section('subheading', 'Volunteer application submitted '.$application->created_at->format('F j, Y'))
@section('back', route('admin.operations.volunteers.index'))
@section('back_label', 'Back to volunteers')
@section('actions')
    <a class="btn btn-secondary" href="tel:{{ $application->phone }}"><x-admin.icon name="phone-call" /> Call {{ $application->first_name }}</a>
    <a class="btn btn-primary" href="mailto:{{ $application->email }}?subject={{ rawurlencode('Your Go Bike volunteer application') }}"><x-admin.icon name="mail" /> Email applicant</a>
@endsection

@use('App\Models\VolunteerApplication')

@section('content')

<div class="detail-grid">

    {{-- Left: everything about the applicant, in one card --}}
    <div class="detail-main">
        <section class="detail-card">

            {{-- Personal Info --}}
            <div class="detail-section">
                <h2 class="detail-section-title">Personal information</h2>
                <dl class="detail-dl">
                    <div><dt>Full name</dt><dd>{{ $application->full_name }}</dd></div>
                    <div><dt>Email</dt><dd class="detail-wrap"><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></dd></div>
                    <div><dt>Phone</dt><dd><a href="tel:{{ $application->phone }}">{{ $application->phone }}</a></dd></div>
                    <div><dt>Age</dt><dd>{{ $application->age }} yrs</dd></div>
                    <div><dt>Occupation</dt><dd>{{ ucfirst(str_replace('-', ' ', $application->occupation_status)) }}</dd></div>
                    @if($application->school)
                        <div><dt>School / Institution</dt><dd>{{ $application->school }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Location --}}
            <div class="detail-section">
                <h2 class="detail-section-title">Location</h2>
                <dl class="detail-dl">
                    <div><dt>Barangay</dt><dd>{{ $application->barangay }}</dd></div>
                    <div><dt>Municipality</dt><dd>{{ $application->municipality }}</dd></div>
                    <div><dt>Province</dt><dd>{{ $application->province }}</dd></div>
                </dl>
            </div>

            {{-- Identity verification: the photos show right here; click one to see it large --}}
            <div class="detail-section">
                <h2 class="detail-section-title">Identity &amp; address proof</h2>
                @if ($application->id_paths)
                    <dl class="detail-dl">
                        <div><dt>Document</dt><dd>{{ $application->id_type_label ?? 'Not specified' }}</dd></div>
                    </dl>
                    <ul class="id-proofs">
                        @foreach ($application->id_paths as $i => $path)
                            @php
                                $url = route('admin.operations.volunteers.id', [$application, $i]);
                                $isImage = \Illuminate\Support\Str::endsWith(strtolower($path), ['.jpg', '.jpeg', '.png', '.webp']);
                            @endphp
                            <li>
                                @if ($isImage)
                                    <button type="button" class="id-proof" data-id-view="{{ $url }}" data-id-title="Document {{ $i + 1 }} of {{ count($application->id_paths) }}" aria-label="View document {{ $i + 1 }} larger">
                                        <img src="{{ $url }}" alt="Uploaded document {{ $i + 1 }}" loading="lazy">
                                    </button>
                                @else
                                    <a class="id-proof id-proof--pdf" href="{{ $url }}" target="_blank" rel="noopener" aria-label="Open PDF document {{ $i + 1 }} in a new tab">
                                        <x-admin.icon name="file-text" />
                                        <span>PDF document</span>
                                        <small>Click to open</small>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="muted">Check that the name matches and that the address is in {{ $application->barangay }}, {{ $application->municipality }}.</p>
                @else
                    <p class="muted">No ID was uploaded. This application was sent before ID verification was required, so ask the volunteer to bring one to training.</p>
                @endif
            </div>

            {{-- Volunteer Details --}}
            <div class="detail-section">
                <h2 class="detail-section-title">Volunteer details</h2>
                <dl class="detail-dl">
                    <div><dt>Availability</dt><dd>{{ ucfirst($application->availability) }}</dd></div>
                </dl>
                @if($application->motivation)
                    <div class="detail-note">
                        <span class="detail-note-label">Motivation</span>
                        <p>{{ $application->motivation }}</p>
                    </div>
                @endif
            </div>

            {{-- Emergency Contact --}}
            <div class="detail-section">
                <h2 class="detail-section-title">Emergency contact</h2>
                <dl class="detail-dl">
                    <div><dt>Name</dt><dd>{{ $application->emergency_name }}</dd></div>
                    <div><dt>Phone</dt><dd><a href="tel:{{ $application->emergency_phone }}">{{ $application->emergency_phone }}</a></dd></div>
                </dl>
            </div>

            @if($application->guardian_name)
            {{-- Guardian Info (minors) --}}
            <div class="detail-section">
                <h2 class="detail-section-title">Guardian / Parent</h2>
                <dl class="detail-dl">
                    <div><dt>Name</dt><dd>{{ $application->guardian_name }}</dd></div>
                    <div><dt>Phone</dt><dd><a href="tel:{{ $application->guardian_phone }}">{{ $application->guardian_phone }}</a></dd></div>
                    <div><dt>Consent given</dt><dd>{{ $application->guardian_consent ? 'Yes' : 'No' }}</dd></div>
                </dl>
            </div>
            @endif

        </section>
    </div>

    {{-- Right: status + actions --}}
    <div class="detail-aside">
        <section class="detail-card">
            <h2 class="detail-card-title">Application status</h2>
            <p class="detail-status"><span class="pill pill-{{ $application->status_pill }}">{{ $application->status_label }}</span></p>
            @if ($application->volunteerClass)
                <dl class="detail-dl">
                    <div><dt>Class</dt><dd><a href="{{ route('admin.operations.volunteers.index', ['class' => $application->volunteerClass->id]) }}">{{ $application->volunteerClass->starts_on->format('D, M j, Y') }}</a></dd></div>
                    <div><dt>Venue</dt><dd>{{ $application->volunteerClass->venue }}</dd></div>
                    @if ($application->volunteerClass->notes)
                        <div><dt>Notes</dt><dd>{{ $application->volunteerClass->notes }}</dd></div>
                    @endif
                    <div><dt>Email</dt><dd>{{ $application->class_emailed_at ? 'Sent '.$application->class_emailed_at->format('M j, g:i A') : 'Not sent yet' }}</dd></div>
                </dl>
                @unless ($application->volunteerClass->isCompleted())
                    <form method="POST" action="{{ route('admin.operations.volunteers.email-class', $application) }}" class="form-actions">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"><x-admin.icon name="mail" /> {{ $application->class_emailed_at ? 'Email class details again' : 'Email class details' }}</button>
                    </form>
                @endunless
            @elseif (in_array($application->application_status, ['pending', 'waiting'], true))
                <p class="muted">Not in a class yet. Training starts once {{ config('volunteer.class_min') }} to {{ config('volunteer.class_max') }} volunteers are waiting.</p>
            @endif
            <form method="POST" action="{{ route('admin.operations.volunteers.status', $application) }}" class="field">
                @csrf
                @method('PATCH')
                <label for="application-status">Change status</label>
                <select id="application-status" name="application_status" class="input" data-autosubmit>
                    @foreach (VolunteerApplication::STATUS_LABELS as $status => $label)
                        <option value="{{ $status }}" @selected($application->application_status === $status)
                            @if ($status === 'rejected')
                                data-confirm-title="Reject this application?"
                                data-confirm-text="{{ $application->full_name }}'s application will be marked rejected. You can change it again later."
                                data-confirm-ok="Reject application"
                            @endif
                        >{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </section>

        <div class="detail-danger">
            <form method="POST" action="{{ route('admin.operations.volunteers.destroy', $application) }}"
                  data-confirm-title="Delete this application?"
                  data-confirm-text="{{ $application->full_name }}'s application, including emergency and guardian contacts, will be permanently deleted."
                  data-confirm-ok="Delete application">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Delete application</button>
            </form>
        </div>
    </div>

</div>
@if ($application->id_paths)
    {{-- Photo viewer: opened by a click on an uploaded document (resources/js/admin/admin.js) --}}
    <dialog class="dlg dlg-photo" id="idViewer" aria-labelledby="idViewerTitle">
        <div class="dlg-head">
            <div class="dlg-head-main">
                <p class="dlg-kicker">{{ $application->full_name }} · {{ $application->id_type_label ?? 'Identity proof' }}</p>
                <h2 id="idViewerTitle" data-id-title>Document</h2>
            </div>
            <button type="button" class="icon-btn" data-dialog-close aria-label="Close"><x-admin.icon name="x" /></button>
        </div>
        <div class="picture-detail-media"><img data-id-img alt="Uploaded document, large view"></div>
        <div class="dlg-foot">
            <a class="btn btn-secondary" data-id-open href="#" target="_blank" rel="noopener"><x-admin.icon name="external-link" /> Open in new tab</a>
            <div class="dlg-foot-main"><button type="button" class="btn btn-primary" data-dialog-close>Close</button></div>
        </div>
    </dialog>
@endif
@endsection
