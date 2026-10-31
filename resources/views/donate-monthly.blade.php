{{--
    Private page where a donor manages their monthly donation (/donate/monthly/{uuid}).
    Linked from the thank-you screen. Cancelling stops all future PayPal charges.
--}}
@php
    $amount = $donation->formatted_total;
    $status = $donation->subscription_status ?? 'active';
    $badgeClass = match ($status) {
        'active' => 'is-ok',
        'suspended' => 'is-warn',
        default => '',
    };
@endphp
<x-layout title="Your Monthly Donation | Go Bike" description="Manage your monthly donation to the Go Bike Project." :show-chatbot="false">
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
        <div class="gbi-card" x-data="{ confirming: false }">
            @if (session('success'))
                <div class="vol-alert is-info" role="status"><span>{{ session('success') }}</span></div>
            @endif
            @if (session('error'))
                <div class="vol-alert is-error" role="alert"><span>{{ session('error') }}</span></div>
            @endif

            <span class="gbi-badge {{ $badgeClass }}">{{ $donation->subscription_label }}</span>
            <h1 class="gbi-title">Your Monthly Donation</h1>

            @if ($status === 'active')
                <p class="gbi-text">
                    Thank you for giving {{ $amount }} every month, {{ $donation->first_name }}. Your support keeps our
                    youth volunteers on the road. PayPal charges your donation automatically each month until you cancel.
                </p>
            @elseif ($status === 'suspended')
                <p class="gbi-text">
                    PayPal couldn't collect your recent monthly donations, so it has paused them. Please update your
                    payment method in your PayPal account, or cancel below if you'd like to stop.
                </p>
            @else
                <p class="gbi-text">
                    Your monthly donation has stopped{{ $donation->cancelled_at ? ' on '.$donation->cancelled_at->format('F j, Y') : '' }}.
                    You will not be charged again. Thank you for everything you've given to the Go Bike Project.
                </p>
            @endif

            <dl class="gbi-summary">
                <div><dt>Reference</dt><dd>{{ $donation->reference }}</dd></div>
                <div><dt>Monthly amount</dt><dd>{{ $amount }} {{ $donation->currency }}</dd></div>
                <div><dt>Started</dt><dd>{{ $donation->created_at->format('F j, Y') }}</dd></div>
                @if ($nextChargeAt)
                    <div><dt>Next donation</dt><dd>{{ $nextChargeAt->format('F j, Y') }}</dd></div>
                @endif
                @if ($donation->payments_count > 0)
                    <div><dt>Monthly payments received</dt><dd>{{ $donation->payments_count }}</dd></div>
                @endif
                <div><dt>Paid with</dt><dd>PayPal</dd></div>
            </dl>

            @if ($donation->canCancelSubscription())
                <div class="gbi-actions" x-show="! confirming">
                    <button type="button" class="gbi-btn-danger" @click="confirming = true">Cancel monthly donation</button>
                    <a href="{{ route('donate') }}" class="gbi-link">Make a one-time donation</a>
                </div>

                <div class="gbi-confirm" x-show="confirming" x-cloak>
                    <p><b>Cancel your monthly donation?</b> PayPal will stop charging you right away. Donations you've already made are not refunded.</p>
                    <div class="gbi-actions">
                        <form method="POST" action="{{ route('donate.monthly.cancel', $donation) }}">
                            @csrf
                            <button type="submit" class="gbi-btn-danger">Yes, cancel it</button>
                        </form>
                        <button type="button" class="gbi-link gbi-link-btn" @click="confirming = false">Keep my donation</button>
                    </div>
                </div>
            @else
                <div class="gbi-actions">
                    <a href="{{ route('donate') }}" class="gbp-btn-primary">Donate again</a>
                </div>
            @endif

            <p class="gbi-help">
                Keep this page's link to manage your donation later. You can also cancel anytime in your PayPal account
                (Settings &rarr; Payments &rarr; Manage automatic payments). Questions? Email
                <a href="mailto:gobike@pydn.org">gobike@pydn.org</a> and mention {{ $donation->reference }}.
            </p>
        </div>
    </section>
</x-layout>
