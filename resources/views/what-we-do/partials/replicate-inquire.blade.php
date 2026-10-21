{{--
    Chapter replication inquiry, with the option to pay the Go Biker's Training fee early online.
    Included by what-we-do/replicate.blade.php (uses its $inclusions). Fees: config/replication.php.
    The total shown here is only a preview; the server always recalculates the amount it charges.
--}}
@php
    $classFee = (int) config('replication.class_fee');
    $classSize = (int) config('replication.class_size');
    $extraFee = (int) config('replication.additional_trainee_fee');
    $maxTrainees = (int) config('replication.max_trainees');
    $refundDays = (int) config('replication.refund_cutoff_days');
@endphp
<section
    id="inquire"
    class="gbp-section"
    x-data="{
        trainees: {{ (int) old('trainees', $classSize) }},
        payment: @js(old('payment', 'later')),
        classSize: {{ $classSize }},
        classFee: {{ $classFee }},
        extraFee: {{ $extraFee }},
        submitting: false,
        get extra() { return Math.max(0, (parseInt(this.trainees, 10) || 0) - this.classSize); },
        get total() { return this.classFee + this.extra * this.extraFee; },
        peso(amount) { return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    }"
>
    <div class="gbp-wrap">
        <header class="gbp-head reveal">
            <span class="gbp-kicker">10 · Get Started</span>
            <h2 class="gbp-h2">Bring Go Bike to Your Community</h2>
            <p class="gbp-sub">
                Send us an inquiry to start a Go Bike chapter. Our team will reach out to plan your partnership
                and training, and you can pay the training fee online in advance if you're ready.
            </p>
        </header>

        <div class="gbp-inquire-grid">
            {{-- Inquiry form --}}
            <div class="gbp-card gbp-inquire-form">
                @if (session('replication_success'))
                    <div class="vol-alert is-info" role="status">
                        <span>{{ session('replication_success') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="vol-alert is-error" role="alert">
                        <ul class="gbp-error-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('replicate.inquiry.store') }}" @submit="submitting = true">
                    @csrf

                    <fieldset class="vol-fieldset">
                        <legend class="vol-legend">Your Organization</legend>
                        <div class="vol-row">
                            <div class="vol-field">
                                <label class="vol-label" for="r-org">Organization name <i>*</i></label>
                                <input id="r-org" name="organization" class="vol-input" value="{{ old('organization') }}" maxlength="150" required placeholder="e.g. SK of Barangay Poblacion">
                            </div>
                            <div class="vol-field">
                                <label class="vol-label" for="r-type">Organization type <i>*</i></label>
                                <select id="r-type" name="organization_type" class="vol-select" required>
                                    <option value="" disabled @selected(! old('organization_type'))>Select a type</option>
                                    @foreach (\App\Models\ReplicationRequest::ORGANIZATION_TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('organization_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="vol-row">
                            <div class="vol-field">
                                <label class="vol-label" for="r-municipality">Municipality / City <i>*</i></label>
                                <input id="r-municipality" name="municipality" class="vol-input" value="{{ old('municipality') }}" maxlength="100" required>
                            </div>
                            <div class="vol-field">
                                <label class="vol-label" for="r-province">Province <i>*</i></label>
                                <input id="r-province" name="province" class="vol-input" value="{{ old('province', 'Pangasinan') }}" maxlength="100" required>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="vol-fieldset">
                        <legend class="vol-legend">Contact Person</legend>
                        <div class="vol-row">
                            <div class="vol-field">
                                <label class="vol-label" for="r-contact">Full name <i>*</i></label>
                                <input id="r-contact" name="contact_name" class="vol-input" value="{{ old('contact_name') }}" maxlength="100" required autocomplete="name">
                            </div>
                            <div class="vol-field">
                                <label class="vol-label" for="r-position">Position <small>(optional)</small></label>
                                <input id="r-position" name="position" class="vol-input" value="{{ old('position') }}" maxlength="100" placeholder="e.g. SK Chairperson">
                            </div>
                        </div>
                        <div class="vol-row">
                            <div class="vol-field">
                                <label class="vol-label" for="r-email">Email <i>*</i></label>
                                <input id="r-email" type="email" name="email" class="vol-input" value="{{ old('email') }}" maxlength="150" required autocomplete="email">
                            </div>
                            <div class="vol-field">
                                <label class="vol-label" for="r-phone">Mobile number <i>*</i></label>
                                <input id="r-phone" type="tel" name="phone" class="vol-input" value="{{ old('phone') }}" maxlength="30" required autocomplete="tel" placeholder="+63 9XX XXX XXXX">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="vol-fieldset">
                        <legend class="vol-legend">Training</legend>
                        <div class="vol-row">
                            <div class="vol-field">
                                <label class="vol-label" for="r-trainees">Number of trainees <i>*</i></label>
                                <input id="r-trainees" type="number" name="trainees" class="vol-input" min="1" max="{{ $maxTrainees }}" x-model.number="trainees" value="{{ old('trainees', $classSize) }}" required>
                                <p class="vol-hint">One class covers up to {{ $classSize }} trainees. For more than {{ $maxTrainees }}, contact us.</p>
                            </div>
                            <div class="vol-field">
                                <label class="vol-label" for="r-date">Preferred training date <small>(optional)</small></label>
                                <input id="r-date" type="date" name="preferred_date" class="vol-input" value="{{ old('preferred_date') }}" min="{{ now()->addDay()->toDateString() }}">
                            </div>
                        </div>
                        <div class="vol-field">
                            <label class="vol-label" for="r-message">Message <small>(optional)</small></label>
                            <textarea id="r-message" name="message" class="vol-textarea" rows="4" maxlength="1000" placeholder="Tell us about your community, your partners, or any questions.">{{ old('message') }}</textarea>
                        </div>
                    </fieldset>

                    <fieldset class="vol-fieldset">
                        <legend class="vol-legend">Payment</legend>
                        <div class="gbp-pay-options">
                            <label class="gbp-pay-option" :class="payment === 'later' && 'is-selected'">
                                <input type="radio" name="payment" value="later" x-model="payment" @checked(old('payment', 'later') === 'later')>
                                <span>
                                    <b>Send inquiry only</b>
                                    <small>We'll contact you to confirm your schedule. Pay later online, or by check at least 3 days before the training.</small>
                                </span>
                            </label>
                            <label class="gbp-pay-option" :class="payment === 'online' && 'is-selected'">
                                <input type="radio" name="payment" value="online" x-model="payment" @checked(old('payment') === 'online')>
                                <span>
                                    <b>Send inquiry and pay now: <span x-text="peso(total)">₱{{ number_format($classFee, 2) }}</span></b>
                                    <small>Pay the full training fee securely through PayMongo with GCash or online banking.</small>
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <label class="vol-check gbp-inquire-consent">
                        <input type="checkbox" name="agree" value="1" @checked(old('agree')) required>
                        <span>
                            I have read and agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms &amp; Conditions</a>,
                            including the refund rules for training payments, and the
                            <a href="{{ route('privacy-policy') }}" target="_blank" rel="noopener">Privacy Policy</a>.
                        </span>
                    </label>

                    <button type="submit" class="vol-submit" :disabled="submitting">
                        <span x-text="payment === 'online' ? 'Continue to payment' : 'Send inquiry'">Send inquiry</span>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </button>
                </form>
            </div>

            {{-- Fee summary + what's included --}}
            <aside class="gbp-inquire-aside">
                <div class="gbp-card">
                    <p class="gbp-label">Training fee</p>
                    <dl class="gbp-fee-rows">
                        <div>
                            <dt>Class fee (up to {{ $classSize }} trainees)</dt>
                            <dd>₱{{ number_format($classFee, 2) }}</dd>
                        </div>
                        <div x-show="extra > 0" x-cloak>
                            <dt><span x-text="extra"></span> additional trainee<span x-show="extra !== 1">s</span> &times; ₱{{ number_format($extraFee) }}</dt>
                            <dd x-text="peso(extra * extraFee)"></dd>
                        </div>
                        <div class="is-total">
                            <dt>Total</dt>
                            <dd x-text="peso(total)">₱{{ number_format($classFee, 2) }}</dd>
                        </div>
                    </dl>
                    <p class="gbp-note">For the 2-day Go Biker's Training (Basic DRRM-CCAM with First Aid &amp; BLS).</p>
                </div>

                <div class="gbp-card">
                    <p class="gbp-label">What's included</p>
                    <ul class="gbp-bullets is-small">
                        @foreach ($inclusions as $inclusion)
                            <li>{{ $inclusion[0] }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="gbp-card">
                    <p class="gbp-label">Payment &amp; refunds</p>
                    <ul class="gbp-bullets is-small">
                        <li>Pay online now with GCash or online banking, or by check to PRI at least 3 days before the training.</li>
                        <li>PRI issues an Official Receipt for your payment.</li>
                        <li>Full refund if we can't schedule your training, or if you cancel at least {{ $refundDays }} days before it. Later cancellations can be moved to a new date.</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>
