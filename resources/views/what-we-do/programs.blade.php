<!-- WHAT WE DO -->

<x-layout
    title="Programs | Go Bike"
    description="Discover how our programs in health, disaster preparedness, and community outreach make an impact."
>

<!-- HERO SECTION -->
<section class="relative min-h-[460px] md:min-h-[500px] flex flex-col justify-center pt-28 pb-16 md:pt-32 md:pb-20 overflow-hidden bg-[#0D1B2A]">
    <!-- Background image -->
    <div class="absolute inset-0 z-0">
        <img
            src="{{ asset('images/whatwedo/whatwedo.png') }}"
            alt="OneGoBike Programs"
            class="w-full h-full object-cover object-[center_35%] opacity-45"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-[#0D1B2A]/80 via-[#0D1B2A]/40 to-[#0D1B2A]/50"></div>
    </div>

    <!-- Content -->
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal w-full">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white mb-5 uppercase tracking-tight leading-tight drop-shadow-lg">
            Impact In<br/>
            <span class="text-[#2FA7FF]">Motion.</span>
        </h1>
        <p class="text-base md:text-lg text-white/70 max-w-2xl mx-auto leading-relaxed">
            We operate dynamic, youth-led programs designed to serve the most pressing needs of our local barangays.
        </p>
    </div>
</section>

<!-- PROGRAMS GRID -->
<section class="py-20 md:py-28 bg-[#F8FAFC]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 lg:gap-16">
            
            @php
            $programsList = [
                [
                    'title' => 'Go Bike',
                    'desc' => 'Transforming young volunteers into active community responders by equipping them with the skills, awareness, and spirit of volunteerism to respond to community needs, promote safety and lend a helping hand when it matters most.',
                    'img_path' => 'images/whatwedo/gobike.jpg',
                    'accent' => '#F97316',
                    'url' => url('/what-we-do/programs/gobike'),
                ],
                [
                    'title' => 'Alwar',
                    'desc' => 'Continuing to empower students within school communities to become proactive young responders, equipped with knowledge, skills, and compassion to promote safety, preparedness, and services to others.',
                    'img_path' => 'images/whatwedo/programs/alwar.jpg',
                    'accent' => '#2FA7FF',
                    'url' => url('/what-we-do/programs/alwar'),
                ],
                [
                    'title' => 'Ronda Kalusugan',
                    'desc' => 'Mobilizing volunteers to reach communities with basic healthcare services, including blood pressure monitoring and blood sugar checking, promoting early awareness and making essential health checks more accessible to everyone.',
                    'img_path' => 'images/whatwedo/ronda.jpg',
                    'accent' => '#F97316',
                    'url' => url('/what-we-do/programs/ronda-kalusugan'),
                ],
                [
                    'title' => 'PadyaKaalaman',
                    'desc' => 'Making knowledge accessible through educational initiatives that inspire curiosity, encourage learning, and open opportunities for learners.',
                    'img_path' => 'images/whatwedo/padyakaalaman.jpg',
                    'accent' => '#132D6B',
                    'url' => url('/what-we-do/programs/padyakaalaman'),
                ],
                [
                    'title' => 'Padyakaisipan',
                    'desc' => 'Promoting mental health awareness by creating safe spaces for conversations, breaking the stigma surrounding mental health, and encouraging young people to prioritize emotional well-being, understanding, and support for one another.',
                    'img_path' => 'images/whatwedo/padyakaisipan.jpg',
                    'accent' => '#2FA7FF',
                    'url' => url('/what-we-do/programs/padyakaisipan'),
                ],
                [
                    'title' => 'Hatake Farm',
                    'desc' => 'Bridging communities together through sustainable farming, food security, and shared harvests that nurture both the land and the people.',
                    'img_path' => 'images/whatwedo/hatake.jpg',
                    'accent' => '#16A34A',
                    'url' => url('/what-we-do/programs/hatake'),
                ],
            ];
            @endphp

            @foreach ($programsList as $idx => $prog)
            <div class="group bg-white rounded-sm overflow-hidden shadow-sm border border-[#F1F5F9] card-lift reveal reveal-delay-{{ $idx % 2 + 1 }}">
                <div class="h-64 overflow-hidden relative">
                    <img src="{{ asset($prog['img_path']) }}" alt="{{ $prog['title'] }}" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-black/10"></div>
                </div>
                <div class="p-8">
                    <h3 class="text-2xl font-heading font-bold text-[#111827] mb-3">
                        @if(!empty($prog['url']))
                            <a href="{{ $prog['url'] }}" class="hover:underline">{{ $prog['title'] }}</a>
                        @else
                            {{ $prog['title'] }}
                        @endif
                    </h3>
                    <p class="text-[#64748B] leading-relaxed mb-6">{{ $prog['desc'] }}</p>
                    <a href="{{ $prog['url'] ?? '#' }}" class="program-card-link">
                        View Details
                        <svg class="w-4 h-4 program-card-link__arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            </div>
            @endforeach

        </div>

    </div>
</section>

<!-- TRAINING TIERS and REQUEST FORM  -->
<section
    id="training-plan"
    class="py-20 md:py-15 bg-[#0D1B2A] relative overflow-hidden scroll-mt-16"
    x-data="{
        selected: @js(old('tier')),
        formVisible: @js($errors->any()),
        tiers: [
            {
                key: 'basic',
                name: 'Basic',
                price: 'Free',
                sub: 'Per person / 1 session',
                desc: 'An introduction to first aid for anyone who wants to know what to do in an emergency.',
                accent: '#16A34A',
                accentBg: 'from-[#16A34A] to-[#15803D]',
                features: ['Basic wound care & CPR demo', 'Emergency contact setup', 'Home safety checklist', '1 onsite training session']
            },
            {
                key: 'standard',
                name: 'Standard',
                price: '₱499',
                sub: 'Per person / 2 sessions',
                desc: 'Hands-on first aid training for people who want to practice real skills and plan for emergencies.',
                accent: '#132D6B',
                accentBg: 'from-[#132D6B] to-[#1e3a8a]',
                features: ['Everything in Basic', 'CPR & BLS certification', 'Disaster readiness planning', 'Emergency kit assembly guide', '2 onsite training sessions', 'Responder group access'],
                popular: true
            },
            {
                key: 'advance',
                name: 'Advance',
                price: '₱999',
                sub: 'Per person / 2 sessions, full module',
                desc: 'The full responder module for people who want to become health advocates in their community.',
                accent: '#9A3412',
                accentBg: 'from-[#9A3412] to-[#c2410c]',
                features: ['Everything in Standard', 'Advanced first aid module', 'Volunteer facilitator track', 'Barangay coordination training', '2 extended training sessions', 'Certificate of completion', 'Priority dispatch access']
            }
        ],
        selectTier(key) {
            const wasVisible = this.formVisible;
            this.selected = key;
            this.formVisible = true;
            if (!wasVisible) {
                this.$nextTick(() => {
                    const el = document.getElementById('training-request-form');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }
        }
    }"
    x-init="if (formVisible) $nextTick(() => document.getElementById('training-request-form')?.scrollIntoView({ block: 'start', behavior: 'instant' }))"
>
    <!-- Subtle grid overlay -->
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 48px 48px;" aria-hidden="true"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <div class="mb-16 reveal">
            <div class="flex items-center gap-3 mb-5">
                <span class="text-[10px] font-bold tracking-[0.25em] uppercase text-[#F97316]">First Aid Training &middot; Priced Per Person</span>
            </div>
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white uppercase tracking-tight leading-none mb-5">
                Learn To<br/><span class="text-[#2FA7FF]">Save Lives</span>
            </h2>
            <p class="text-white/50 text-base max-w-xl leading-relaxed">
                Learn first aid and emergency preparedness from Go Bike's trained youth responders. Every plan is priced per person, and we hold the sessions where you are: your home, school, office or barangay hall. Pick a plan below to get started.
            </p>
        </div>

        <!-- Tier Cards -->
        <div id="tier-cards-section" class="grid grid-cols-1 lg:grid-cols-3 gap-6 reveal scroll-mt-24">
            <template x-for="tier in tiers" :key="tier.key">
                <div
                    @click="selectTier(tier.key)"
                    class="relative cursor-pointer flex flex-col transition-all duration-300 overflow-hidden"
                    :class="selected === tier.key
                        ? 'ring-2 ring-offset-4 ring-offset-[#0D1B2A] scale-[1.02]'
                        : 'ring-1 ring-white/10 hover:ring-white/25 hover:-translate-y-1'"
                    :style="selected === tier.key ? 'ring-color:' + tier.accent : ''"
                >
                    <!-- Card ring color override via CSS var hack -->
                    <div class="absolute inset-0 rounded-none transition-all duration-300 pointer-events-none z-20"
                         :style="selected === tier.key ? 'box-shadow: 0 0 0 2px ' + tier.accent + ', 0 0 0 6px #0D1B2A' : ''">
                    </div>

                    <!-- Popular badge -->
                    <template x-if="tier.popular">
                        <div class="absolute top-4 right-4 z-30 text-[9px] font-black tracking-widest uppercase bg-[#F97316] text-white px-2.5 py-1">
                            Most Popular
                        </div>
                    </template>

                    <!-- Selected badge -->
                    <div
                        class="absolute top-4 left-4 z-30 w-7 h-7 rounded-full flex items-center justify-center transition-all duration-300"
                        :style="selected === tier.key ? 'background:' + tier.accent : 'background: rgba(255,255,255,0.08)'"
                    >
                        <svg class="w-3.5 h-3.5 text-white transition-opacity duration-300" :class="selected === tier.key ? 'opacity-100' : 'opacity-30'" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </div>

                    <!-- Header gradient -->
                    <div class="h-36 flex flex-col justify-end p-6 relative" :class="'bg-gradient-to-br ' + tier.accentBg">
                        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 70% 20%, white 0%, transparent 60%);"></div>
                        <span class="text-xs font-bold tracking-[0.2em] uppercase text-white/70 mb-1" x-text="tier.name"></span>
                        <div class="text-4xl font-heading font-black text-white tracking-tight" x-text="tier.price"></div>
                        <p class="text-white/60 text-xs mt-1" x-text="tier.sub"></p>
                    </div>

                    <!-- Body -->
                    <div class="flex-grow flex flex-col p-6 bg-[#111827]">
                        <p class="text-white/50 text-sm leading-relaxed mb-6" x-text="tier.desc"></p>
                        <ul class="space-y-3 flex-grow mb-8">
                            <template x-for="(feat, fi) in tier.features" :key="fi">
                                <li class="flex items-start gap-3 text-sm text-white/70">
                                    <span class="w-4 h-4 shrink-0 mt-0.5 rounded-full flex items-center justify-center" :style="'background:' + tier.accent + '22'">
                                        <svg class="w-2.5 h-2.5" :style="'color:' + tier.accent" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                    <span x-text="feat"></span>
                                </li>
                            </template>
                        </ul>

                        <!-- CTA Button -->
                        <button
                            type="button"
                            class="w-full py-3 text-xs font-black tracking-widest uppercase transition-all duration-300 border"
                            :style="selected === tier.key
                                ? 'background:' + tier.accent + '; color: white; border-color:' + tier.accent
                                : 'background: transparent; color: rgba(255,255,255,0.6); border-color: rgba(255,255,255,0.15)'"
                            x-text="selected === tier.key ? '✓ Selected' : 'Select ' + tier.name"
                        ></button>
                    </div>
                </div>
            </template>
        </div>

        @if($thanks = session('training_success'))
            <script>
                // Jump straight to the note; the site's smooth scrolling would otherwise glide down from the top
                document.documentElement.style.scrollBehavior = 'auto';
                window.addEventListener('load', () => {
                    document.getElementById('training-thanks')?.scrollIntoView({ block: 'start' });
                    document.documentElement.style.scrollBehavior = '';
                });
            </script>
            <!-- Thank-you note after a successful request -->
            <div id="training-thanks" x-data="{ open: true }" x-show="open" class="tr-thanks" role="status">

                <div class="tr-thanks__intro">
                    <svg class="tr-thanks__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <h3 class="tr-thanks__title">Thank you, {{ $thanks['first_name'] ?? 'friend' }}!</h3>
                    <p class="tr-thanks__lead">
                        We received your training request. A Go Bike coordinator will get in touch to set your schedule.
                    </p>
                    @isset($thanks['plan'])
                        <dl class="tr-thanks__summary">
                            <div class="tr-thanks__row">
                                <dt>Plan</dt>
                                <dd>{{ $thanks['plan'] }}</dd>
                            </div>
                            <div class="tr-thanks__row">
                                <dt>We'll reply to</dt>
                                <dd>{{ $thanks['email'] }}</dd>
                            </div>
                        </dl>
                    @endisset
                </div>

                <div class="tr-thanks__next">
                    <h4 class="tr-thanks__heading">What happens next</h4>
                    <ol class="tr-thanks__steps">
                        <li class="tr-thanks__step tr-thanks__step--done">
                            <span class="tr-thanks__marker" aria-hidden="true">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </span>
                            <div>
                                <p class="tr-thanks__step-title">Request received <span class="tr-thanks__step-when">· today</span></p>
                                <p class="tr-thanks__step-text">Your details are now with our dispatch team.</p>
                            </div>
                        </li>
                        <li class="tr-thanks__step tr-thanks__step--current">
                            <span class="tr-thanks__marker" aria-hidden="true">2</span>
                            <div>
                                <p class="tr-thanks__step-title">We contact you <span class="tr-thanks__step-when">· within 48 hours</span></p>
                                <p class="tr-thanks__step-text">We'll call or email you to agree on a date, time and the number of participants.</p>
                            </div>
                        </li>
                        <li class="tr-thanks__step">
                            <span class="tr-thanks__marker" aria-hidden="true">3</span>
                            <div>
                                <p class="tr-thanks__step-title">Training day</p>
                                <p class="tr-thanks__step-text">Our youth responders come to your home, school or barangay hall.</p>
                            </div>
                        </li>
                    </ol>

                    <div class="tr-thanks__actions">
                        <button type="button" class="tr-thanks__again"
                            @click="open = false; $nextTick(() => document.getElementById('tier-cards-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
                            Request another training
                        </button>
                        <a href="{{ url('/contact') }}" class="tr-thanks__contact">
                            Questions? <strong>Contact us</strong>
                        </a>
                    </div>
                </div>

            </div>
        @endif

        <!-- Step 2: Request Form — only visible after tier selected -->
        <div
            id="training-request-form"
            x-show="formVisible"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 translate-y-10"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-16 scroll-mt-24"
            style="display:none;"
        >
            <!-- Divider -->
            <div class="flex items-center gap-5 mb-12">
                <div class="flex-1 h-px bg-white/10"></div>
                <span class="text-[10px] font-bold tracking-widest uppercase text-white/25">Schedule Your Training</span>
                <div class="flex-1 h-px bg-white/10"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-12 items-start">

                <!-- Left: form context -->
                <div class="lg:col-span-2">
                    <h3 class="text-3xl font-heading font-bold text-white uppercase tracking-tight mb-4 leading-tight">
                        Schedule Your<br/>Training Session
                    </h3>
                    <p class="text-white/45 text-sm leading-relaxed mb-8">
                        Fill out the form and our dispatch team will contact you within 24–48 hours to confirm your schedule.
                    </p>
                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-[#F97316]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
                            </div>
                            <div>
                                <div class="text-white text-sm font-semibold mb-0.5">Response within 48 hours</div>
                                <div class="text-white/40 text-xs">Our team reviews all requests quickly</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-[#F97316]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </div>
                            <div>
                                <div class="text-white text-sm font-semibold mb-0.5">On-site where you are</div>
                                <div class="text-white/40 text-xs">Home, school or barangay hall, anywhere in Pangasinan</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-[#F97316]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0"/></svg>
                            </div>
                            <div>
                                <div class="text-white text-sm font-semibold mb-0.5">Certified youth responders</div>
                                <div class="text-white/40 text-xs">Trained, vetted, and accredited volunteers</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    @if($errors->any())
                        <div class="mb-5 px-5 py-4 border border-[#F87171]/40 bg-[#F87171]/10 text-sm text-[#FECACA]" role="alert">
                            @error('tier')
                                Please choose a training plan above, then submit again.
                            @else
                                Please check the fields below and try again.
                                <ul class="list-disc pl-5 mt-2 space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @enderror
                        </div>
                    @endif
                    <form action="{{ route('training-request.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <input type="hidden" name="tier" :value="selected">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-[10px] font-bold tracking-widest uppercase text-white/40 mb-2">Full Name <span class="text-[#F97316]">*</span></label>
                                <input type="text" name="name" placeholder="Juan dela Cruz" required value="{{ old('name') }}"
                                    class="w-full px-4 py-3.5 bg-white/5 border border-white/10 text-sm text-white placeholder-white/25 focus:outline-none focus:border-[#2FA7FF] focus:bg-white/8 transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold tracking-widest uppercase text-white/40 mb-2">Phone Number</label>
                                <input type="tel" name="phone" placeholder="+63 900 000 0000" value="{{ old('phone') }}"
                                    class="w-full px-4 py-3.5 bg-white/5 border border-white/10 text-sm text-white placeholder-white/25 focus:outline-none focus:border-[#2FA7FF] focus:bg-white/8 transition-all duration-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold tracking-widest uppercase text-white/40 mb-2">Email Address <span class="text-[#F97316]">*</span></label>
                            <input type="email" name="email" placeholder="juan@gmail.com" required value="{{ old('email') }}"
                                class="w-full px-4 py-3.5 bg-white/5 border border-white/10 text-sm text-white placeholder-white/25 focus:outline-none focus:border-[#2FA7FF] focus:bg-white/8 transition-all duration-200">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold tracking-widest uppercase text-white/40 mb-2">Home Address <span class="text-[#F97316]">*</span></label>
                            <input type="text" name="address" placeholder="Barangay, Municipality, Pangasinan" required value="{{ old('address') }}"
                                class="w-full px-4 py-3.5 bg-white/5 border border-white/10 text-sm text-white placeholder-white/25 focus:outline-none focus:border-[#2FA7FF] focus:bg-white/8 transition-all duration-200">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold tracking-widest uppercase text-white/40 mb-2">Message / Preferred Schedule</label>
                            <textarea name="message" rows="4" placeholder="Number of participants, preferred schedule, special requirements or questions..."
                                class="w-full px-4 py-3.5 bg-white/5 border border-white/10 text-sm text-white placeholder-white/25 focus:outline-none focus:border-[#2FA7FF] focus:bg-white/8 transition-all duration-200 resize-none">{{ old('message') }}</textarea>
                        </div>

                        <p class="text-xs text-white/40 leading-relaxed">
                            By submitting, you agree to our
                            <a href="{{ route('terms') }}" class="text-[#2FA7FF] hover:underline">Terms</a>
                            and acknowledge our
                            <a href="{{ route('privacy-policy') }}" class="text-[#2FA7FF] hover:underline">Privacy Policy</a>.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center pt-2">
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2.5 px-10 py-4 text-xs font-black tracking-widest uppercase bg-[#F97316] text-white hover:bg-[#ea6a0e] transition-colors duration-200">
                                Submit Request
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                            </button>
                            <button type="button" @click="selected = null; $nextTick(() => { const el = document.getElementById('tier-cards-section'); if(el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); })"
                                class="text-xs font-semibold text-white/35 hover:text-white/70 tracking-wider transition-colors underline underline-offset-4">
                                Change Plan
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>
</section>

<!-- replicate card -->
@include('partials.replicate-card')


<!-- 
     CTA
      -->
<section class="py-30 bg-white text-center px-4 border-t border-[#F1F5F9]">
    <div class="max-w-2xl mx-auto reveal">
        <h2 class="text-3xl md:text-5xl font-heading font-bold text-[#111827] mb-6 uppercase tracking-tight">Support Our Work</h2>
        <p class="text-[#64748B] mb-10 text-lg">Our programs rely on the generosity of individuals and partners who believe in the power of youth.</p>
        <a href="{{ url('/donate') }}" class="btn-wbr btn-wbr-dark">
            <span>Make a Donation</span>
        </a>
    </div>
</section>

</x-layout>
