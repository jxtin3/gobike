<x-layout
    title="Volunteer | Go Bike"
    description="Sign up as a Go Biker volunteer and join youth cyclist-responders delivering health outreach, disaster preparedness, and first response across Pangasinan."
>
@php
    $facts = [
        ['13-25', 'Age range'],
        ['2 Days', 'DRRM-CCAM, FA & BLS training'],
        ['Pangasinan', 'Community-based chapters'],
        [$classMin.'–'.$classMax, 'Volunteers per class'],
    ];

    $activities = [
        ['Health Outreach', 'Support Ronda Kalusugan and other health outreach in underserved barangays.'],
        ['Disaster Preparedness', 'Help communities get ready before typhoons, floods, and other hazards strike.'],
        ['Rapid Community Response', 'Ride in as a supplementary first responder, coordinating with local authorities.'],
        ['Youth Advocacy', 'Take part in PadyaKaisipan and other youth-led campaigns and advocacies.'],
    ];

    $benefits = [
        ['Free Hands-on Training', '2-day Basic DRRM-CCAM with First Aid & Basic Life Support, given to your whole class.'],
        ['Certificate of Completion', 'Recognition of your DRRM-CCAM with FA & BLS training.'],
        ['Go Biker ID', 'Accredited by Padyarescue Inc. and the Municipal DRRMO.'],
        ['Membership & Insurance', 'Philippine Red Cross Premier Bronze membership and insurance.'], // EDIT: confirm this applies to individual volunteers
        ['Leadership Growth', 'Life skills and leadership training, with a path to chapter officer roles.'],
    ];

    $whoCanJoin = [
        'Aged 13 to 25',
        'Written parent or guardian consent if you are 13 to 17',
        'Physically fit and with no current illness',
        'Willing to complete the basic training before field activities',
    ];

    $toPrepare = [
        'The barangay and municipality where you live',
        'A photo or scan of your school ID (preferred if you are a student), another valid ID, or a barangay clearance, indigency or residency certificate',
        'An emergency contact (name and mobile number)',
        'Parent or guardian details, if you are under 18',
    ];

    $steps = [ // EDIT: confirm the real process with PadyaRescue
        ['Sign Up', 'Fill out the volunteer form below and upload your ID. Our team verifies it and calls you.'],
        ['Wait for a Full Class', "Training starts once {$classMin} to {$classMax} volunteers have signed up. We call or text you with the date and venue."],
        ['Training', 'Complete the free 2-day DRRM-CCAM with FA & BLS training with your class.'],
        ['Deployment', 'Join your chapter and take your place in the duty rotation.'],
    ];

    $faqs = [
        ['What is the minimum age to volunteer?',
         'Our youth responder program accepts volunteers aged 13 to 25. Minors aged 13-17 need written parental consent to take part in active field training and dispatch activities.'],
        ['Do I need training before I sign up?',
         'No. You do not need any knowledge of basic first aid or any other skill before you sign up. Basic DRRM-CCAM with First Aid & BLS training is part of the program, so you will be trained before going into the field. What we do ask is that you are physically fit and have no current illness.'],
        ['What ID do I need to upload?',
         'A school ID is preferred if you are a student. If you do not have one, or you are not a student, upload another valid ID, or a barangay clearance, certificate of indigency or certificate of residency. We use it to confirm your identity and where you live, and only authorized staff can see it.'],
        ['When does my training start?',
         "Volunteer training is free and given to a whole class at once. A class starts once {$classMin} to {$classMax} volunteers have signed up. When your class is complete, we call or text you with the date and venue, so keep your mobile number reachable."],
        ['Can I get trained sooner?',
         'Yes. If you do not want to wait for a full class, you can book <a href="' . url('/what-we-do#training-plan') . '">individual first aid training</a>. It is paid, and it starts on the date you choose.'],
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

                    @php
                        $full = $queue >= $classMin;
                        $filled = min($queue, $classMin);
                    @endphp
                    <div class="vol-class" role="status">
                        <div class="vol-class__top">
                            <span class="vol-class__label">Next volunteer class</span>
                            <span class="vol-class__count"><strong>{{ $filled }}</strong> of {{ $classMin }} volunteers</span>
                        </div>
                        <div class="vol-class__bar" aria-hidden="true"><span style="width: {{ round($filled / max(1, $classMin) * 100) }}%"></span></div>
                        <p class="vol-class__text">
                            @if ($full)
                                This class is complete and will be scheduled soon. Sign up now to join it (up to {{ $classMax }}) or the next one.
                            @else
                                Free training starts once {{ $classMin }} to {{ $classMax }} volunteers have signed up. We'll email you the date and venue, and may call you too.
                            @endif
                        </p>
                        <p class="vol-class__alt">Want to start sooner? <a href="{{ url('/what-we-do#training-plan') }}">See individual training plans</a>, paid and on your own date.</p>
                    </div>
                    <ul>
                        <li>Open to youth aged 13 to 25</li>
                        <li>Parent or guardian consent needed if under 18</li>
                        <li>No prior experience required</li>
                        <li>Physically fit, with no current illness</li>
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
                        <h3 x-ref="thanks" tabindex="-1">Application Received!</h3>
                        <p x-show="position" x-cloak>
                            You're <strong x-text="'#' + position"></strong> in line for the next volunteer class.
                            Training starts once <span x-text="classMin"></span> to <span x-text="classMax"></span> volunteers have signed up.
                            We'll email you the date and venue (check your spam folder too), and may call you.
                        </p>
                        <p x-show="!position">Thank you for signing up to volunteer. Our team will review your application and reach out using the email or mobile number you provided.</p>
                        <button type="button" @click="submitted = false; showCard('form')">Submit another application</button>
                    </div>

                    {{-- Form --}}
                    <form x-show="!submitted" x-ref="form" tabindex="-1" @submit.prevent="submitForm">

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

                        {{-- Identity verification --}}
                        <fieldset class="vol-fieldset">
                            <legend class="vol-legend">Verify Your Identity</legend>
                            <p class="vol-note" role="note">
                                <span x-show="form.status === 'student'">You are a student, so please upload your <strong>school ID</strong>. If you do not have it with you, a barangay clearance, certificate of indigency or certificate of residency also works.</span>
                                <span x-show="form.status !== 'student'">To confirm who you are and where you live, upload a valid ID, or a barangay clearance, certificate of indigency or certificate of residency.</span>
                            </p>

                            <div class="vol-field" style="margin-bottom:1rem;">
                                <label class="vol-label" for="v-id-type">Document type <i>*</i></label>
                                <select id="v-id-type" class="vol-select" x-model="form.idType" required>
                                    <option value="" disabled>Select…</option>
                                    @foreach ($idTypes as $value => $label)
                                        @if ($value === 'school-id')
                                            <option value="{{ $value }}" x-text="form.status === 'student' ? '{{ $label }} (preferred)' : '{{ $label }}'">{{ $label }}</option>
                                        @else
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="vol-field">
                                <span class="vol-label" id="v-id-file-label">Upload document <i>*</i></span>

                                {{-- Chosen files: one row each, with its own remove button --}}
                                <ul class="vol-files" x-show="idFiles.length" x-cloak aria-label="Uploaded documents">
                                    <template x-for="(item, i) in idFiles" :key="item.file.name + item.file.size">
                                        <li class="vol-file">
                                            <span class="vol-file__thumb">
                                                <img x-show="item.preview" :src="item.preview" alt="">
                                                <svg x-show="!item.preview" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                                </svg>
                                            </span>
                                            <span class="vol-file__meta">
                                                <strong x-text="item.file.name"></strong>
                                                <small x-text="sizeLabel(item.file)"></small>
                                            </span>
                                            <button type="button" class="vol-file__remove" @click="removeId(i)" :aria-label="'Remove ' + item.file.name">
                                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </li>
                                    </template>
                                </ul>

                                {{-- The upload rectangle: choose one or several files at once --}}
                                <label for="v-id-file" class="vol-drop" :class="{ 'is-compact': idFiles.length, 'has-error': idError }" x-show="idFiles.length < idMax">
                                    <input id="v-id-file" class="vol-drop__input" type="file" multiple
                                           accept="image/jpeg,image/png,image/webp,application/pdf"
                                           aria-labelledby="v-id-file-label" aria-describedby="v-id-hint v-id-error"
                                           :aria-invalid="idError ? 'true' : 'false'" @change="pickIds($event)">

                                    <span class="vol-drop__empty">
                                        <span class="vol-drop__icon" aria-hidden="true">
                                            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                            </svg>
                                        </span>
                                        <span class="vol-drop__text">
                                            <strong x-text="idFiles.length ? 'Add another photo or file' : 'Tap to take a photo or choose files'"></strong>
                                            <small>You can select more than one · JPG, PNG, WEBP or PDF · up to 5 MB each</small>
                                        </span>
                                    </span>
                                </label>

                                <p class="vol-hint" x-show="idFiles.length" x-cloak x-text="idFiles.length + ' of ' + idMax + ' files added'"></p>

                                <p id="v-id-error" class="vol-error" role="alert" x-show="idError" x-cloak x-text="idError"></p>

                                <p id="v-id-hint" class="vol-note" role="note" style="margin-top:0.75rem;">Upload a clear photo, scan or PDF (JPG, PNG, WEBP or PDF, up to 5 MB each). You can add the front and back of your ID, or more than one document. Make sure your name and address are readable to verify your application.</p>
                            </div>
                        </fieldset>

                        {{-- Volunteering --}}
                        <fieldset class="vol-fieldset">
                            <legend class="vol-legend">Your Volunteering</legend>

                            <div class="vol-alert is-info" role="note">
                                <span><strong>Physical fitness:</strong> Go Bike volunteers ride and do field work, so you must be physically fit and have no current illness to take part. You do not need any first aid knowledge, because we train you.</span>
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
                            <span>I have read and agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms &amp; Conditions</a> and consent to the collection and use of my information, including my uploaded ID, to verify and process my application, as described in the <a href="{{ route('privacy-policy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</span>
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