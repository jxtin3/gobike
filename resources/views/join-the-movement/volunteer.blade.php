<x-layout
    title="Volunteer | Go Bike Project"
    description="Sign up as a Go Biker volunteer and join youth cyclist-responders delivering health outreach, disaster preparedness, and first response across Pangasinan."
>
@php
    $facts = [
        ['13-25', 'Age range'],
        ['2 Days', 'DRRM-CCAM, FA & BLS training'],
        ['Pangasinan', 'Community-based chapters'],
        ['Bike-powered', 'Rapid community response'],
    ];

    $activities = [
        ['Health Outreach', 'Support Ronda Kalusugan and other health outreach in underserved barangays.'],
        ['Disaster Preparedness', 'Help communities get ready before typhoons, floods, and other hazards strike.'],
        ['Rapid Community Response', 'Ride in as a supplementary first responder, coordinating with local authorities.'],
        ['Youth Advocacy', 'Take part in PadyaKaisipan and other youth-led campaigns and advocacies.'],
    ];

    $benefits = [
        ['Hands-on Training', '2-day Basic DRRM-CCAM with First Aid & Basic Life Support.'],
        ['Certificate of Completion', 'Recognition of your DRRM-CCAM with FA & BLS training.'],
        ['Go Biker ID', 'Accredited by Padyarescue Inc. and the Municipal DRRMO.'],
        ['Membership & Insurance', 'Philippine Red Cross Premier Bronze membership and insurance.'], // EDIT: confirm this applies to individual volunteers
        ['Leadership Growth', 'Life skills and leadership training, with a path to chapter officer roles.'],
    ];

    $whoCanJoin = [
        'Aged 13 to 25',
        'Written parent or guardian consent if you are 13 to 17',
        'Willing to complete the basic training before field activities',
        'Able to ride a bicycle and commit to scheduled duty rotations', // EDIT
    ];

    $toPrepare = [
        'The barangay and municipality where you live',
        'An emergency contact (name and mobile number)',
        'Parent or guardian details, if you are under 18',
    ];

    $steps = [ // EDIT: confirm the real process with PadyaRescue
        ['Sign Up', 'Fill out the volunteer form below.'],
        ['Screening', 'Our team reviews your application and reaches out to you.'],
        ['Training', 'Complete the 2-day DRRM-CCAM with FA & BLS training.'],
        ['Deployment', 'Join your chapter and take your place in the duty rotation.'],
    ];

    $interests = [
        'Health outreach',
        'Disaster preparedness',
        'First aid & response',
        'Bike repair & maintenance',
        'Advocacy campaigns',
        'Documentation & media',
    ];

    $faqs = [
        ['What is the minimum age to volunteer?',
         'Our youth responder program accepts volunteers aged 13 to 25. Minors aged 13-17 need written parental consent to take part in active field training and dispatch activities.'],
        ['Do I need training before I sign up?',
         'No. Basic DRRM-CCAM with First Aid & BLS training is part of the program, so you will be trained before going into the field.'],
        ['Who do I contact in an immediate emergency?',
         'Go Bike is a supplementary first responder organization. Always dial 911 or your local MDRRMO first. We coordinate closely with local authorities for dispatch and triaging.'],
        ['I live outside Pangasinan. Can I still take part?',
         'You can bring Go Bike to your own community. See the <a href="' . route('replicate') . '">chapter replication program</a> or <a href="' . url('/contact') . '">reach out to us</a>.'],
    ];
@endphp

<div class="gbp">

    {{-- HERO --}}
    <section class="gbp-hero gbp-hero--photo">
        <div class="gbp-hero-bg" aria-hidden="true">
            <img src="{{ asset('images/homepage/go_bike.jpg') }}" alt="" fetchpriority="high" style="object-position: center 40%;">
        </div>
        <div class="gbp-hero-shade" aria-hidden="true"></div>

        <div class="gbp-wrap">
            <h1 class="gbp-title">Become a <em>Go Biker</em></h1>
            <p class="gbp-lead">
                Join the Go Bike Project and become part of a community of trained, empowered, and service-driven volunteers. 
                Together, let's promote health, strengthen disaster resilience, and create safer, more resilient communities!
            </p>
        </div>
    </section>

    {{-- QUICK FACTS --}}
    <div class="vol-facts">
        <ul class="vol-facts-list">
            @foreach ($facts as $fact)
                <li><strong>{{ $fact[0] }}</strong><span>{{ $fact[1] }}</span></li>
            @endforeach
        </ul>
    </div>

    {{-- WHAT YOU'LL DO --}}
    <section class="gbp-section">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">01 · The Work</span>
                <h2 class="gbp-h2">What You'll Do</h2>
                <p class="gbp-sub">Go Bikers are force multipliers for a more active, healthier, safer, and disaster-resilient community.</p>
            </header>
            <div class="gbp-grid gbp-grid-4">
                @foreach ($activities as $a)
                    <article class="gbp-card reveal">
                        <h3 class="gbp-h3">{{ $a[0] }}</h3>
                        <p class="gbp-note">{{ $a[1] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- WHAT YOU GET --}}
    <section class="gbp-section is-soft">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">02 · The Benefits</span>
                <h2 class="gbp-h2">What You Get</h2>
            </header>
            <div class="gbp-grid gbp-grid-5">
                @foreach ($benefits as $b)
                    <article class="gbp-card reveal">
                        <span class="gbp-check"></span>
                        <h3 class="gbp-h3">{{ $b[0] }}</h3>
                        <p class="gbp-note">{{ $b[1] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- REQUIREMENTS --}}
    <section class="gbp-section">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">03 · Eligibility</span>
                <h2 class="gbp-h2">Requirements</h2>
            </header>
            <div class="gbp-grid gbp-grid-2">
                <aside class="gbp-card reveal">
                    <h3 class="gbp-h3">Who can join</h3>
                    <ul class="gbp-bullets">
                        @foreach ($whoCanJoin as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </aside>
                <aside class="gbp-card reveal">
                    <h3 class="gbp-h3">What to prepare when signing up</h3>
                    <ul class="gbp-bullets">
                        @foreach ($toPrepare as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </aside>
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section class="gbp-section is-soft">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">04 · The Process</span>
                <h2 class="gbp-h2">How It Works</h2>
            </header>
            <div class="gbp-grid gbp-grid-4">
                @foreach ($steps as $step)
                    <article class="gbp-card reveal">
                        <span class="gbp-num vol-step-num">{{ $loop->iteration }}</span>
                        <h3 class="gbp-h3">{{ $step[0] }}</h3>
                        <p class="gbp-note">{{ $step[1] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- SIGN-UP --}}
    <section id="signup" class="vol-signup">
        <div class="gbp-wrap">
            <div class="vol-signup-grid">

                <aside class="vol-aside reveal">
                    <span class="gbp-eyebrow">Sign Up</span>
                    <h2>Ready to ride with us?</h2>
                    <p>Tell us a little about yourself. It takes about five minutes, and we'll reach out about the next steps.</p>
                    <ul>
                        <li>Open to youth aged 13 to 25</li>
                        <li>Parent or guardian consent needed if under 18</li>
                        <li>No prior experience required</li>
                    </ul>
                    <p class="vol-aside-note">
                        Questions first? <a href="{{ url('/contact') }}">Reach out to us</a>.
                    </p>
                </aside>

                <div class="vol-form-card reveal" x-data="volunteerForm()">

                    {{-- Success --}}
                    <div x-show="submitted" x-cloak class="vol-success">
                        <div class="vol-success-icon">
                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                        <h3>Application Received!</h3>
                        <p>Thank you for signing up to volunteer. Our team will review your application and reach out using the email or mobile number you provided.</p>
                        <button type="button" @click="submitted = false">Submit another application</button>
                    </div>

                    {{-- Form --}}
                    <form x-show="!submitted" @submit.prevent="submitForm">

                        {{-- About you --}}
                        <fieldset class="vol-fieldset">
                            <legend class="vol-legend">About You</legend>

                            <div class="vol-row">
                                <div class="vol-field">
                                    <label class="vol-label" for="v-first">First Name <i>*</i></label>
                                    <input id="v-first" class="vol-input" type="text" x-model="form.firstName" required autocomplete="given-name" placeholder="Juan">
                                </div>
                                <div class="vol-field">
                                    <label class="vol-label" for="v-last">Last Name <i>*</i></label>
                                    <input id="v-last" class="vol-input" type="text" x-model="form.lastName" required autocomplete="family-name" placeholder="dela Cruz">
                                </div>
                            </div>

                            <div class="vol-row">
                                <div class="vol-field">
                                    <label class="vol-label" for="v-email">Email Address <i>*</i></label>
                                    <input id="v-email" class="vol-input" type="email" x-model="form.email" required autocomplete="email" placeholder="juan@example.com">
                                </div>
                                <div class="vol-field">
                                    <label class="vol-label" for="v-phone">Mobile Number <i>*</i></label>
                                    <input id="v-phone" class="vol-input" type="tel" x-model="form.phone" required autocomplete="tel" placeholder="+63 9XX XXX XXXX">
                                </div>
                            </div>

                            <div class="vol-row">
                                <div class="vol-field">
                                    <label class="vol-label" for="v-age">Age <i>*</i></label>
                                    <input id="v-age" class="vol-input" type="number" inputmode="numeric" x-model="form.age" required placeholder="e.g. 17">
                                </div>
                                <div class="vol-field">
                                    <label class="vol-label" for="v-status">I am currently <i>*</i></label>
                                    <select id="v-status" class="vol-select" x-model="form.status" required>
                                        <option value="" disabled>Select…</option>
                                        <option value="student">A student</option>
                                        <option value="working">Working</option>
                                        <option value="out-of-school">An out-of-school youth</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="vol-field">
                                <label class="vol-label" for="v-school">School / Organization <small>(optional)</small></label>
                                <input id="v-school" class="vol-input" type="text" x-model="form.school" placeholder="Where you study or work">
                            </div>

                            {{-- Age notices --}}
                            <div x-show="ageState === 'young' || ageState === 'old'" x-cloak x-collapse>
                                <div class="vol-alert is-warn" style="margin-top:1rem;">
                                    <span>
                                        Our youth responder program accepts volunteers aged 13 to 25.
                                        Please <a href="{{ url('/contact') }}">contact us</a> to ask about other ways to help.
                                    </span>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Address --}}
                        <fieldset class="vol-fieldset">
                            <legend class="vol-legend">Where You Live</legend>
                            <div class="vol-row is-3">
                                <div class="vol-field">
                                    <label class="vol-label" for="v-brgy">Barangay <i>*</i></label>
                                    <input id="v-brgy" class="vol-input" type="text" x-model="form.barangay" required>
                                </div>
                                <div class="vol-field">
                                    <label class="vol-label" for="v-city">Municipality / City <i>*</i></label>
                                    <input id="v-city" class="vol-input" type="text" x-model="form.municipality" required>
                                </div>
                                <div class="vol-field">
                                    <label class="vol-label" for="v-prov">Province <i>*</i></label>
                                    <input id="v-prov" class="vol-input" type="text" x-model="form.province" required>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Volunteering --}}
                        <fieldset class="vol-fieldset">
                            <legend class="vol-legend">Your Volunteering</legend>

                            <div class="vol-field" style="margin-bottom:1rem;">
                                <span class="vol-label">Areas you're interested in <small>(choose any)</small></span>
                                <div class="vol-chips">
                                    @foreach ($interests as $interest)
                                        <label class="vol-chip">
                                            <input type="checkbox" value="{{ $interest }}" x-model="form.interests">
                                            <span>{{ $interest }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="vol-row">
                                <div class="vol-field">
                                    <label class="vol-label" for="v-avail">Availability <i>*</i></label>
                                    <select id="v-avail" class="vol-select" x-model="form.availability" required>
                                        <option value="" disabled>Select…</option>
                                        <option value="weekdays">Weekdays</option>
                                        <option value="weekends">Weekends</option>
                                        <option value="both">Weekdays &amp; weekends</option>
                                        <option value="flexible">Flexible</option>
                                    </select>
                                </div>
                                <div class="vol-field">
                                    <span class="vol-label">Do you have a bicycle? <i>*</i></span>
                                    <div class="vol-chips">
                                        <label class="vol-chip">
                                            <input type="radio" name="has_bike" value="yes" x-model="form.hasBike" required>
                                            <span>Yes</span>
                                        </label>
                                        <label class="vol-chip">
                                            <input type="radio" name="has_bike" value="no" x-model="form.hasBike">
                                            <span>No</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="vol-field">
                                <label class="vol-label" for="v-why">Why do you want to volunteer? <small>(optional)</small></label>
                                <textarea id="v-why" class="vol-textarea" rows="4" maxlength="500" x-model="form.motivation" placeholder="Tell us a little about yourself and what motivates you…"></textarea>
                                <p class="vol-hint" style="text-align:right;" x-text="(form.motivation || '').length + ' / 500'"></p>
                            </div>
                        </fieldset>

                        {{-- Emergency contact --}}
                        <fieldset class="vol-fieldset">
                            <legend class="vol-legend">Emergency Contact</legend>
                            <div class="vol-row">
                                <div class="vol-field">
                                    <label class="vol-label" for="v-em-name">Contact Name <i>*</i></label>
                                    <input id="v-em-name" class="vol-input" type="text" x-model="form.emergencyName" required>
                                </div>
                                <div class="vol-field">
                                    <label class="vol-label" for="v-em-phone">Contact Number <i>*</i></label>
                                    <input id="v-em-phone" class="vol-input" type="tel" x-model="form.emergencyPhone" required placeholder="+63 9XX XXX XXXX">
                                </div>
                            </div>
                        </fieldset>

                        {{-- Guardian (only for 13–17) --}}
                        <div x-show="isMinor" x-cloak x-collapse>
                            <fieldset class="vol-fieldset">
                                <legend class="vol-legend">Parent / Guardian</legend>
                                <div class="vol-alert is-info">
                                    <span>Because you are under 18, a parent or guardian must consent to your participation in field training and dispatch activities.</span>
                                </div>
                                <div class="vol-row">
                                    <div class="vol-field">
                                        <label class="vol-label" for="v-g-name">Guardian Name <i>*</i></label>
                                        <input id="v-g-name" class="vol-input" type="text" x-model="form.guardianName" :required="isMinor">
                                    </div>
                                    <div class="vol-field">
                                        <label class="vol-label" for="v-g-phone">Guardian Number <i>*</i></label>
                                        <input id="v-g-phone" class="vol-input" type="tel" x-model="form.guardianPhone" :required="isMinor" placeholder="+63 9XX XXX XXXX">
                                    </div>
                                </div>
                                <label class="vol-check">
                                    <input type="checkbox" x-model="form.guardianConsent" :required="isMinor">
                                    <span>My parent or guardian is aware of and consents to my volunteering with the Go Bike Project.</span>
                                </label>
                            </fieldset>
                        </div>

                        {{-- Consent + submit --}}
                        <label class="vol-check" style="margin-bottom:1.25rem;">
                            <input type="checkbox" x-model="form.agree" required>
                            <span>I agree to the collection and use of my information for volunteer processing, as described in the <a href="{{ url('/privacy-policy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</span>
                        </label>

                        <div x-show="error" x-cloak class="vol-alert is-error">
                            <span x-text="errorMessage"></span>
                        </div>

                        <button type="submit" class="vol-submit" :disabled="loading || !canSubmit">
                            <span x-show="!loading">Submit Application</span>
                            <span x-show="loading" x-cloak style="display:inline-flex;align-items:center;gap:.5rem;">
                                <svg class="animate-spin" width="16" height="16" fill="none" viewBox="0 0 24 24">
                                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Sending…
                            </span>
                            <svg x-show="!loading" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                            </svg>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="gbp-section">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">05 · Questions</span>
                <h2 class="gbp-h2">Volunteer FAQ</h2>
            </header>
            <div class="vol-faq" x-data="{ open: 0 }">
                @foreach ($faqs as $i => $faq)
                    <div class="vol-faq-item">
                        <button type="button" class="vol-faq-q" :class="open === {{ $i }} ? 'is-open' : ''"
                                :aria-expanded="open === {{ $i }}" @click="open = open === {{ $i }} ? null : {{ $i }}">
                            <span>{{ $faq[0] }}</span>
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-collapse x-cloak>
                            <p class="vol-faq-a">{!! $faq[1] !!}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-30 bg-white text-center px-4 border-t border-[#F1F5F9]">
        <div class="max-w-2xl mx-auto reveal">
            <h2 class="text-3xl md:text-5xl font-heading font-bold text-[#111827] mb-6 uppercase tracking-tight">Can't Volunteer Right Now?</h2>
            <p class="text-[#64748B] mb-10 text-lg">You can still power the movement. Every donation helps keep Go Bikers trained, equipped, and on the road.</p>
            <a href="{{ url('/donate') }}" class="btn-wbr btn-wbr-dark">
                <span>Make a Donation</span>
            </a>
        </div>
    </section>

</div>

<x-slot:scripts>
    <script src="{{ asset('js/volunteer.js') }}"></script>
</x-slot:scripts>

</x-layout>