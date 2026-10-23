{{--
    Status page for one chapter replication inquiry (/replicate-go-bike/inquiry/{uuid}).
    Shows the hand-off to PayMongo, the payment confirmation, or the option to pay again.
--}}
@php
    $amount = '₱'.number_format((float) $inquiry->amount_php, 2);
    $paymentLabel = match ($inquiry->payment_status) {
        'paid' => 'Paid'.($inquiry->paid_at ? ' on '.$inquiry->paid_at->format('F j, Y') : ''),
        'pending' => 'Waiting for payment',
        'refunded' => 'Refunded',
        default => 'Not paid yet',
    };
@endphp
<x-layout
    :title="'Inquiry '.$inquiry->reference.' | Go Bike'"
    description="Status of your Go Bike chapter replication inquiry."
    :show-chatbot="false"
>
    <x-slot:head>
        <meta name="robots" content="noindex">
        <style>
            #site-header.site-header {
                background-color: #0D1B2A !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            }
        </style>
    </x-slot:head>

    <section class="gbi">
        <div class="gbi-card">
            @if (session('error'))
                <div class="vol-alert is-error" role="alert"><span>{{ session('error') }}</span></div>
            @endif

            @if ($checkoutUrl)
                <p class="gbi-kicker">Inquiry saved &middot; {{ $inquiry->reference }}</p>
                <h1 class="gbi-title">Taking you to secure payment&hellip;</h1>
                <p class="gbi-text">
                    You'll pay {{ $amount }} through PayMongo with GCash or online banking.
                    If the payment page doesn't open, use the button below.
                </p>
                <div class="gbi-actions">
                    <a href="{{ $checkoutUrl }}" class="gbp-btn-primary">Continue to payment</a>
                </div>
                <script>window.location.replace(@js($checkoutUrl));</script>
            @elseif ($inquiry->isPaid())
                <span class="gbi-badge is-ok">Payment received</span>
                <h1 class="gbi-title">Thank you, {{ $inquiry->organization }}!</h1>
                <p class="gbi-text">
                    We received your payment of {{ $amount }} for the Go Biker's Training. Our team will contact you
                    within 48 hours to confirm your training schedule. PayMongo has emailed you a payment receipt,
                    and PRI will issue an Official Receipt.
                </p>
                <div class="gbi-actions">
                    <a href="{{ route('replicate') }}" class="gbi-link">&larr; Back to Replicate Go Bike</a>
                </div>
            @elseif ($inquiry->payment_status === 'refunded')
                <span class="gbi-badge">Refunded</span>
                <h1 class="gbi-title">Your payment was refunded</h1>
                <p class="gbi-text">Your training payment for this inquiry has been refunded. If you have questions, please contact us.</p>
            @elseif (! $inquiry->canPayOnline())
                <span class="gbi-badge">{{ ucfirst($inquiry->status) }}</span>
                <h1 class="gbi-title">This inquiry is {{ $inquiry->status }}</h1>
                <p class="gbi-text">Online payment is no longer available for this inquiry. If you need help, please contact us.</p>
            @else
                @php $notCompleted = $cancelled || $inquiry->payment_status === 'pending'; @endphp
                <span class="gbi-badge is-warn">{{ $notCompleted ? 'Payment not completed' : 'Inquiry received' }}</span>
                <h1 class="gbi-title">{{ $notCompleted ? 'Your payment was not completed' : 'Your inquiry is saved' }}</h1>
                <p class="gbi-text">
                    Don't worry, your inquiry is saved. You can pay the training fee online now, or wait for our team
                    to contact you and pay by check at least 3 days before the training.
                </p>
                <div class="gbi-actions">
                    <a href="{{ route('replicate.inquiry.pay', $inquiry) }}" class="gbp-btn-primary">Pay {{ $amount }} now</a>
                    <a href="{{ route('replicate') }}" class="gbi-link">Back to Replicate Go Bike</a>
                </div>
            @endif

            <dl class="gbi-summary">
                <div><dt>Reference</dt><dd>{{ $inquiry->reference }}</dd></div>
                <div><dt>Organization</dt><dd>{{ $inquiry->organization }}</dd></div>
                <div><dt>Trainees</dt><dd>{{ $inquiry->trainees }}</dd></div>
                <div><dt>Preferred date</dt><dd>{{ $inquiry->preferred_date?->format('F j, Y') ?? 'To be scheduled' }}</dd></div>
                <div><dt>Training fee</dt><dd>{{ $amount }}</dd></div>
                <div><dt>Payment</dt><dd>{{ $paymentLabel }}</dd></div>
            </dl>

            <p class="gbi-help">
                Keep this page's link for your records. Questions? Email
                <a href="mailto:gobike@pydn.org">gobike@pydn.org</a> and mention your reference {{ $inquiry->reference }}.
            </p>
        </div>
    </section>
</x-layout>
