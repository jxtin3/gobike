@extends('admin.operations.layout')
@php
    $who = $sos->user?->name ?? 'Deleted resident';
    $pills = ['sent' => 'bad', 'responding' => 'warn', 'resolved' => 'ok'];
@endphp
@section('title', 'SOS from '.$who)
@section('heading', 'SOS from '.$who)
@section('subheading', 'Sent '.$sos->created_at->format('F j, Y \a\t g:i A').' · '.$sos->created_at->diffForHumans())
@section('back', route('admin.operations.sos.index'))
@section('back_label', 'Back to SOS requests')
@section('actions')
    @if ($sos->user?->mobile)
        <a class="btn btn-secondary" href="tel:{{ $sos->user->mobile }}"><x-admin.icon name="phone-call" /> Call {{ $sos->user->mobile }}</a>
    @endif
    @if ($sos->canMoveTo('responding'))
        <form method="POST" action="{{ route('admin.operations.sos.status', $sos) }}">
            @csrf
            @method('PATCH')
            <button type="submit" name="status" value="responding" class="btn btn-primary"><x-admin.icon name="activity" /> Responding</button>
        </form>
    @endif
    @if ($sos->canMoveTo('resolved'))
        <form method="POST" action="{{ route('admin.operations.sos.status', $sos) }}"
              data-confirm-title="Resolve this SOS request?"
              data-confirm-text="{{ $who }} will see it as resolved in the app. Do this only when they no longer need help."
              data-confirm-ok="Resolve" data-confirm-tone="primary">
            @csrf
            @method('PATCH')
            <button type="submit" name="status" value="resolved" class="btn btn-secondary"><x-admin.icon name="check" /> Resolved</button>
        </form>
    @endif
@endsection

@section('content')
<p class="detail-status">
    <span class="pill pill-{{ $pills[$sos->status] ?? 'neutral' }}">{{ $sos->status_label }}</span>
    @if ($sos->handler)
        <span class="muted">
            · {{ $sos->status === 'resolved' ? 'resolved' : 'answered' }} by {{ $sos->handler->name }}
            {{ ($sos->resolved_at ?? $sos->responded_at)?->diffForHumans() }}
        </span>
    @endif
</p>

<div class="detail-columns">
    <article class="detail-card">
        <h2 class="detail-card-title">Where</h2>
        <dl class="detail-dl">
            <div><dt>Barangay</dt><dd>{{ $sos->barangay }}, {{ $sos->municipality }}</dd></div>
            <div><dt>Landmark</dt><dd>{{ $sos->landmark }}</dd></div>
            <div>
                <dt>Phone location</dt>
                <dd>
                    @if ($sos->map_url)
                        <a href="{{ $sos->map_url }}" target="_blank" rel="noopener"><x-admin.icon name="map" /> Open in Google Maps</a>
                    @else
                        Not shared (the resident's phone did not send a GPS position)
                    @endif
                </dd>
            </div>
        </dl>
    </article>

    <article class="detail-card">
        <h2 class="detail-card-title">Resident</h2>
        <dl class="detail-dl">
            <div><dt>Name</dt><dd>{{ $who }}</dd></div>
            <div><dt>Mobile</dt><dd>@if ($sos->user?->mobile)<a href="tel:{{ $sos->user->mobile }}">{{ $sos->user->mobile }}</a>@else — @endif</dd></div>
            <div><dt>Home barangay</dt><dd>{{ $sos->user?->barangay ?: '—' }}</dd></div>
            <div><dt>Email</dt><dd>{{ $sos->user?->email ?: '—' }}</dd></div>
        </dl>
    </article>
</div>

<article class="detail-card">
    <h2 class="detail-card-title">What happened</h2>
    <div class="detail-copy">{!! $sos->description ? nl2br(e($sos->description)) : '<span class="muted">No description was written.</span>' !!}</div>

    @if ($sos->image_url)
        <img class="detail-image" decoding="async" src="{{ $sos->image_url }}" alt="Photo attached to the SOS request from {{ $who }}">
    @endif
    @if ($sos->video_url)
        <video class="detail-image" src="{{ $sos->video_url }}" controls preload="metadata" playsinline>
            <a href="{{ $sos->video_url }}">Download the video</a>
        </video>
    @endif
    @unless ($sos->image_url || $sos->video_url)
        <p class="muted">No photo or video was attached.</p>
    @endunless
</article>

@if ($earlier->isNotEmpty())
    <section class="report-section">
        <h2>Earlier requests from {{ $who }}</h2>
        <ul class="detail-dl">
            @foreach ($earlier as $old)
                <li>
                    <a href="{{ route('admin.operations.sos.show', $old) }}">{{ $old->created_at->format('M j, Y g:i A') }}</a>
                    · {{ $old->barangay }} · <span class="pill pill-{{ $pills[$old->status] ?? 'neutral' }}">{{ $old->status_label }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
