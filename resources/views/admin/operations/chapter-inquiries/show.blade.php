@extends('admin.operations.layout')
@section('title', $inquiry->organization.' — Chapter inquiry')
@section('heading', $inquiry->organization)
@section('subheading', 'Chapter inquiry '.$inquiry->reference.' · submitted '.$inquiry->created_at->format('F j, Y'))
@section('back', route('admin.operations.chapter-inquiries.index'))
@section('back_label', 'Back to chapter inquiries')

@use('App\Models\ReplicationRequest')

@section('content')
@php
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
            <h2 class="detail-card-title">Contact person</h2>
            <dl class="detail-dl">
                <div><dt>Name</dt><dd>{{ $inquiry->contact_name }}</dd></div>
                <div><dt>Position</dt><dd>{{ $inquiry->position ?: '—' }}</dd></div>
                <div><dt>Email</dt><dd><a href="mailto:{{ $inquiry->email }}?subject={{ $emailSubject }}">{{ $inquiry->email }}</a></dd></div>
                <div><dt>Phone</dt><dd><a href="tel:{{ $inquiry->phone }}">{{ $inquiry->phone }}</a></dd></div>
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
                <div><dt>Status</dt><dd><span class="pill pill-{{ $inquiry->payment_pill }}">{{ $inquiry->payment_label }}</span></dd></div>
                @if (in_array($inquiry->payment_status, ['paid', 'refunded'], true) && $inquiry->paid_at)
                    <div><dt>Paid on</dt><dd>{{ $inquiry->paid_at->format('F j, Y g:i A') }}</dd></div>
                @endif
                <div><dt>PayMongo payment ID</dt><dd>{{ $inquiry->paymongo_payment_id ?: '—' }}</dd></div>
                <div><dt>PayMongo checkout ID</dt><dd>{{ $inquiry->paymongo_session_id ?: '—' }}</dd></div>
            </dl>
        </section>
    </div>

    <div class="detail-aside">
        <section class="detail-card">
            <h2 class="detail-card-title">Inquiry status</h2>
            <p class="detail-status"><span class="pill pill-{{ $inquiry->status_pill }}">{{ $inquiry->status_label }}</span></p>
            <form method="POST" action="{{ route('admin.operations.chapter-inquiries.update', $inquiry) }}" class="field">
                @csrf
                @method('PATCH')
                <label for="inquiry-status">Change status</label>
                <select id="inquiry-status" name="status" class="input" data-autosubmit>
                    @foreach (ReplicationRequest::STATUSES as $status)
                        <option value="{{ $status }}" @selected($inquiry->status === $status)
                            @if ($status === 'cancelled')
                                data-confirm-title="Cancel this inquiry?"
                                data-confirm-text="The inquiry will be marked cancelled and the organization can no longer pay online."
                                data-confirm-ok="Mark cancelled"
                            @endif
                        >{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </form>
        </section>

        <section class="detail-card">
            <h2 class="detail-card-title">Payment status</h2>
            <form method="POST" action="{{ route('admin.operations.chapter-inquiries.update', $inquiry) }}" class="field">
                @csrf
                @method('PATCH')
                <label for="inquiry-payment">Record a check payment or refund</label>
                <select id="inquiry-payment" name="payment_status" class="input" data-autosubmit>
                    @foreach (ReplicationRequest::PAYMENT_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($inquiry->payment_status === $value)
                            @if ($value === 'paid')
                                data-confirm-title="Mark this training as paid?"
                                data-confirm-text="Only do this for a check or cash payment you have received. Online payments are marked paid automatically."
                                data-confirm-ok="Mark paid" data-confirm-tone="ok"
                            @elseif ($value === 'refunded')
                                data-confirm-title="Mark this payment as refunded?"
                                data-confirm-text="Process the refund in the PayMongo dashboard first. This only records it here."
                                data-confirm-ok="Mark refunded"
                            @endif
                        >{{ $label }}</option>
                    @endforeach
                </select>
                <p class="hint">Online payments are marked paid automatically. Process refunds in the PayMongo dashboard, then set this to Refunded.</p>
            </form>
        </section>

        <div class="detail-actions">
            <a class="btn btn-primary" href="mailto:{{ $inquiry->email }}?subject={{ $emailSubject }}">
                <x-admin.icon name="mail" /> Email contact person
            </a>
            @unless ($inquiry->isPaid())
                <form method="POST" action="{{ route('admin.operations.chapter-inquiries.destroy', $inquiry) }}"
                      data-confirm-title="Delete this inquiry?"
                      data-confirm-text="The inquiry from {{ $inquiry->organization }} will be permanently deleted."
                      data-confirm-ok="Delete inquiry">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Delete inquiry</button>
                </form>
            @endunless
        </div>
    </div>

</div>
@endsection
