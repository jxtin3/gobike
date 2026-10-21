@extends('admin.operations.layout')
@section('title', $inquiry->organization.' — Chapter Inquiry')
@section('heading', $inquiry->organization)
@section('subheading', 'Chapter replication inquiry '.$inquiry->reference)
@section('back', route('admin.operations.chapter-inquiries.index'))
@section('back_label', 'Back to Chapter Inquiries')

@section('content')
@php
    $statusColors = ['pending' => 'warn', 'contacted' => 'info', 'scheduled' => 'info', 'completed' => 'ok', 'cancelled' => 'neutral'];
    $paymentColors = ['unpaid' => 'neutral', 'pending' => 'warn', 'paid' => 'ok', 'refunded' => 'bad'];
    $paymentLabels = ['unpaid' => 'Not paid', 'pending' => 'Awaiting online payment', 'paid' => 'Paid', 'refunded' => 'Refunded'];
    $emailSubject = rawurlencode('Go Bike chapter inquiry '.$inquiry->reference);
@endphp

<div class="detail-grid">

    <div class="detail-main">
        <section class="detail-card">
            <h2 class="detail-card-title">Organization</h2>
            <dl class="detail-dl">
                <div><dt>Name</dt><dd>{{ $inquiry->organization }}</dd></div>
                <div><dt>Type</dt><dd>{{ $inquiry->organization_type_label }}</dd></div>
                <div><dt>Municipality / City</dt><dd>{{ $inquiry->municipality }}</dd></div>
                <div><dt>Province</dt><dd>{{ $inquiry->province }}</dd></div>
            </dl>
        </section>

        <section class="detail-card">
            <h2 class="detail-card-title">Contact Person</h2>
            <dl class="detail-dl">
                <div><dt>Name</dt><dd>{{ $inquiry->contact_name }}</dd></div>
                <div><dt>Position</dt><dd>{{ $inquiry->position ?: '—' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $inquiry->email }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $inquiry->phone }}</dd></div>
            </dl>
        </section>

        <section class="detail-card">
            <h2 class="detail-card-title">Training</h2>
            <dl class="detail-dl">
                <div><dt>Trainees</dt><dd>{{ $inquiry->trainees }}</dd></div>
                <div><dt>Preferred date</dt><dd>{{ $inquiry->preferred_date?->format('F j, Y') ?? 'Not specified' }}</dd></div>
                <div><dt>Training fee</dt><dd>₱{{ number_format((float) $inquiry->amount_php, 2) }}</dd></div>
            </dl>
            @if ($inquiry->message)
                <div class="detail-note">
                    <span class="detail-note-label">Message</span>
                    <p>{{ $inquiry->message }}</p>
                </div>
            @endif
        </section>

        <section class="detail-card">
            <h2 class="detail-card-title">Payment</h2>
            <dl class="detail-dl">
                <div><dt>Status</dt><dd><span class="pill pill-{{ $paymentColors[$inquiry->payment_status] ?? 'neutral' }}">{{ $paymentLabels[$inquiry->payment_status] ?? ucfirst($inquiry->payment_status) }}</span></dd></div>
                <div><dt>Paid on</dt><dd>{{ $inquiry->paid_at?->format('F j, Y g:i A') ?? '—' }}</dd></div>
                <div><dt>PayMongo payment ID</dt><dd>{{ $inquiry->paymongo_payment_id ?: '—' }}</dd></div>
                <div><dt>PayMongo checkout ID</dt><dd>{{ $inquiry->paymongo_session_id ?: '—' }}</dd></div>
            </dl>
        </section>
    </div>

    <div class="detail-aside">
        <section class="detail-card">
            <h2 class="detail-card-title">Inquiry Status</h2>
            <div style="margin-bottom:16px">
                <span class="pill pill-{{ $statusColors[$inquiry->status] ?? 'neutral' }}" style="font-size:13px;padding:6px 14px">{{ ucfirst($inquiry->status) }}</span>
            </div>
            <form method="POST" action="{{ route('admin.operations.chapter-inquiries.update', $inquiry) }}" class="status-form">
                @csrf
                @method('PATCH')
                <label class="input-label" for="inquiry-status">Update status</label>
                <select id="inquiry-status" name="status" class="input" style="width:100%" onchange="this.form.submit()">
                    @foreach (\App\Models\ReplicationRequest::STATUSES as $status)
                        <option value="{{ $status }}" @selected($inquiry->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </form>
        </section>

        <section class="detail-card">
            <h2 class="detail-card-title">Payment Status</h2>
            <form method="POST" action="{{ route('admin.operations.chapter-inquiries.update', $inquiry) }}" class="status-form">
                @csrf
                @method('PATCH')
                <label class="input-label" for="inquiry-payment">Record a check payment or refund</label>
                <select id="inquiry-payment" name="payment_status" class="input" style="width:100%" onchange="this.form.submit()">
                    @foreach ($paymentLabels as $value => $label)
                        <option value="{{ $value }}" @selected($inquiry->payment_status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            <p style="margin:10px 0 0;font-size:12px;color:var(--ink-3)">
                Online payments are marked paid automatically. Process refunds in the PayMongo dashboard, then set this to Refunded.
            </p>
        </section>

        <section class="detail-card">
            <h2 class="detail-card-title">Submitted</h2>
            <p style="font-size:14px;color:var(--ink-2)">{{ $inquiry->created_at->format('F j, Y') }}</p>
            <p style="font-size:13px;color:var(--ink-3)">{{ $inquiry->created_at->format('g:i A') }}</p>
        </section>

        <div style="margin-top:4px">
            <a class="btn btn-secondary" style="width:100%;justify-content:center" href="mailto:{{ $inquiry->email }}?subject={{ $emailSubject }}">
                <x-admin.icon name="mail" /> Email Contact Person
            </a>
        </div>
    </div>

</div>
@endsection
