<x-layout
    title="Reach Us | Go Bike Project"
    description="Get in touch with OneGoBike. Whether you want to volunteer, partner, or simply ask a question, we'd love to hear from you."
>

<!-- HERO SECTION -->
<section class="relative min-h-[460px] md:min-h-[500px] flex flex-col justify-center pt-28 pb-16 md:pt-32 md:pb-20 overflow-hidden bg-[#0D1B2A]">

    <!-- Background image -->
    <div class="absolute inset-0 z-0">
        <img
            src="{{ asset('images/reachus/reachus.jpg') }}"
            alt="Go Bike community"
            class="w-full h-full object-cover object-[center_35%] opacity-45"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-[#0D1B2A]/80 via-[#0D1B2A]/40 to-[#0D1B2A]/60"></div>
    </div>

    <!-- Decorative orbs -->
    <div class="absolute top-20 left-10 w-72 h-72 rounded-full bg-[#2FA7FF]/8 blur-3xl pointer-events-none z-0"></div>
    <div class="absolute bottom-10 right-10 w-96 h-96 rounded-full bg-[#F97316]/8 blur-3xl pointer-events-none z-0"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal w-full">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white mb-5 uppercase tracking-tight leading-tight drop-shadow-lg">
            We'd Love To<br/>
            <span class="text-[#F97316]">Hear From You.</span>
        </h1>
        <p class="text-base md:text-lg text-white/60 max-w-xl mx-auto leading-relaxed">
            Have a question, a partnership idea, or want to get involved? Reach out - our team is always ready to connect.
        </p>
    </div>
</section>

<!-- CONTACT CARDS -->
<section class="py-10 md:py-15 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14 reveal">
            <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-3">Ways To Connect</span>
            <h2 class="text-3xl md:text-4xl font-heading font-bold text-[#111827] uppercase tracking-tight">Find Us Anywhere</h2>
            <p class="mt-3 text-[#64748B] max-w-xl mx-auto text-sm leading-relaxed">
                Choose the channel that works best for you. We're across multiple platforms and always happy to connect.
            </p>
        </div>

        {{-- Each card is one link, so tapping anywhere on it opens the map, email, page or phone.
             The "Open Map"-style text is a <span>: the global `a { color: inherit }` rule in
             site-globals.css overrode the colour when it was its own link, which made it invisible. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

            <!-- Location -->
            <a href="https://maps.google.com/?q=15.954506,120.217065" target="_blank" rel="noopener"
               aria-label="Visit us: Bugallon, Pangasinan, Philippines. Open in Google Maps (opens in a new tab)"
               class="group relative block p-8 bg-[#F8FAFC] border border-[#E2E8F0] rounded-sm overflow-hidden reveal card-lift hover:border-[#2FA7FF]/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2FA7FF] transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-[#2FA7FF]/5 rounded-full blur-2xl pointer-events-none group-hover:bg-[#2FA7FF]/10 transition-colors duration-500"></div>
                <div class="w-12 h-12 rounded-sm bg-[#2FA7FF]/10 flex items-center justify-center mb-5 group-hover:bg-[#2FA7FF]/20 transition-colors duration-300">
                    <svg class="w-5 h-5 text-[#2FA7FF]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                    </svg>
                </div>
                <span class="text-[9px] font-bold tracking-[0.2em] uppercase text-[#2FA7FF] mb-2 block">Location</span>
                <h3 class="text-base font-heading font-bold text-[#111827] mb-2 uppercase tracking-wide">Visit Us</h3>
                <p class="text-sm text-[#64748B] leading-relaxed">Bugallon, Pangasinan, Philippines</p>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-[#0369A1] group-hover:gap-2.5 transition-all duration-200">
                    Open Map
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>

            <!-- Email -->
            <a href="mailto:gobike@pydn.org"
               aria-label="Write to us at gobike@pydn.org"
               class="group relative block p-8 bg-[#F8FAFC] border border-[#E2E8F0] rounded-sm overflow-hidden reveal reveal-delay-1 card-lift hover:border-[#F97316]/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#F97316] transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-[#F97316]/5 rounded-full blur-2xl pointer-events-none group-hover:bg-[#F97316]/10 transition-colors duration-500"></div>
                <div class="w-12 h-12 rounded-sm bg-[#F97316]/10 flex items-center justify-center mb-5 group-hover:bg-[#F97316]/20 transition-colors duration-300">
                    <svg class="w-5 h-5 text-[#F97316]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                </div>
                <span class="text-[9px] font-bold tracking-[0.2em] uppercase text-[#F97316] mb-2 block">Email</span>
                <h3 class="text-base font-heading font-bold text-[#111827] mb-2 uppercase tracking-wide">Write To Us</h3>
                <p class="text-sm text-[#64748B] leading-relaxed break-all">gobike@pydn.org</p>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-[#C2410C] group-hover:gap-2.5 transition-all duration-200">
                    Send Email
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>

            <!-- Facebook -->
            <a href="https://www.facebook.com/share/19bhjcFGCL/" target="_blank" rel="noopener"
               aria-label="Follow us on Facebook: @gobikeprojectofficial (opens in a new tab)"
               class="group relative block p-8 bg-[#F8FAFC] border border-[#E2E8F0] rounded-sm overflow-hidden reveal reveal-delay-2 card-lift hover:border-[#2FA7FF]/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2FA7FF] transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-[#2FA7FF]/5 rounded-full blur-2xl pointer-events-none group-hover:bg-[#2FA7FF]/10 transition-colors duration-500"></div>
                <div class="w-12 h-12 rounded-sm bg-[#2FA7FF]/10 flex items-center justify-center mb-5 group-hover:bg-[#2FA7FF]/20 transition-colors duration-300">
                    <svg class="w-5 h-5 text-[#2FA7FF]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <span class="text-[9px] font-bold tracking-[0.2em] uppercase text-[#2FA7FF] mb-2 block">Facebook</span>
                <h3 class="text-base font-heading font-bold text-[#111827] mb-2 uppercase tracking-wide">Follow Us</h3>
                <p class="text-sm text-[#64748B] leading-relaxed">@gobikeprojectofficial</p>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-[#0369A1] group-hover:gap-2.5 transition-all duration-200">
                    View Page
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>

            <!-- Hotline -->
            <a href="tel:+639764290145"
               aria-label="Call our emergency hotline, +63 976 429 0145"
               class="group relative block p-8 bg-[#132D6B] text-white rounded-sm overflow-hidden reveal reveal-delay-3 card-lift focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2FA7FF]">
                <div class="absolute top-0 right-0 w-40 h-40 bg-[#2FA7FF]/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="w-12 h-12 rounded-sm bg-white/10 flex items-center justify-center mb-5 group-hover:bg-white/20 transition-colors duration-300">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                    </svg>
                </div>
                <span class="text-[9px] font-bold tracking-[0.2em] uppercase text-[#2FA7FF] mb-2 block relative z-10">Emergency / Hotline</span>
                <h3 class="text-base font-heading font-bold text-white mb-2 uppercase tracking-wide relative z-10">Call Us</h3>
                <p class="text-sm text-white/70 leading-relaxed relative z-10">Available for urgent community needs</p>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-[#2FA7FF] group-hover:gap-2.5 transition-all duration-200 relative z-10">
                    View Number
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>

        </div>
    </div>
</section>

<!-- MESSAGE FORM + SIDEBAR -->
<section class="py-10 md:py-15 bg-[#F8FAFC] relative overflow-x-clip">
    <div class="absolute top-0 left-0 w-96 h-96 bg-[#132D6B]/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-0 w-72 h-72 bg-[#F97316]/5 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 lg:gap-16 items-start">

            <!-- Sidebar -->
            <div class="lg:col-span-2 reveal">
                <!-- <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-4">Send A Message</span> -->
                <h2 class="text-3xl md:text-4xl font-heading font-bold text-[#111827] uppercase tracking-tight leading-tight mb-6">
                    Let's Start<br/>
                    <span class="text-[#132D6B]">A Conversation.</span>
                </h2>
                <p class="text-sm text-[#64748B] leading-relaxed mb-8">
                    Whether you're looking to volunteer, explore partnership opportunities, or just want to learn more about our programs - we'd love to hear your story.
                </p>

                <!-- What to expect -->
                <div class="space-y-5">
                    @foreach([
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>',
                            'title' => 'Quick Response',
                            'desc'  => 'We aim to respond to all messages within 24 hours.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>',
                            'title' => 'Personal Reply',
                            'desc'  => 'Every inquiry is handled by a real team member.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>',
                            'title' => 'Confidential',
                            'desc'  => 'Your information is kept private and secure.',
                        ],
                    ] as $item)
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-sm bg-[#132D6B]/10 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4.5 h-4.5 text-[#132D6B]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                {!! $item['icon'] !!}
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-[#111827] tracking-wide uppercase">{{ $item['title'] }}</p>
                            <p class="text-xs text-[#64748B] leading-relaxed mt-0.5">{{ $item['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Social quick links -->
                <div class="mt-10 pt-8 border-t border-[#E2E8F0]">
                    <p class="text-[10px] font-bold tracking-[0.2em] uppercase text-[#64748B] mb-4">Also Find Us On</p>
                    <div class="flex items-center gap-3">
                        <a href="https://www.facebook.com/gobikeprojectofficial" target="_blank" rel="noopener"
                           class="w-9 h-9 rounded-none bg-[#132D6B] text-white flex items-center justify-center hover:bg-[#2FA7FF] transition-colors duration-200" aria-label="Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="lg:col-span-3 reveal reveal-delay-1">
                <div class="bg-white border border-[#E2E8F0] rounded-sm p-8 md:p-10 shadow-sm">

                    <h3 class="text-lg font-heading font-bold text-[#111827] uppercase tracking-wide mb-7">
                        Send Us A Message
                    </h3>

                    <!-- Success / Error messages (shown via Alpine) -->
                    <div  x-data="contactForm()">

                        <div x-show="submitted && !error" x-cloak
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="flex flex-col items-center justify-center py-16 gap-5 text-center">
                            <div class="w-16 h-16 rounded-sm bg-green-50 border border-green-200 flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg font-heading font-bold text-[#111827] uppercase tracking-wide mb-2">Message Sent!</p>
                                <p class="text-sm text-[#64748B]">Thank you for reaching out. We'll get back to you within 24 hours.</p>
                            </div>
                            <button @click="submitted = false; error = false"
                                    class="text-xs font-semibold text-[#2FA7FF] underline underline-offset-2 hover:text-[#132D6B] transition-colors">
                                Send another message
                            </button>
                        </div>

                        <form x-show="!submitted" @submit.prevent="submitForm" class="space-y-5">

                            <!-- Name + Email row -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="contact-name" class="block text-[10px] font-bold tracking-[0.15em] uppercase text-[#64748B] mb-2">Full Name <span class="text-[#F97316]">*</span></label>
                                    <input
                                        id="contact-name"
                                        x-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Juan dela Cruz"
                                        class="w-full px-4 py-3 text-sm text-[#111827] bg-[#F8FAFC] border border-[#E2E8F0] rounded-none focus:outline-none focus:ring-2 focus:ring-[#2FA7FF]/30 focus:border-[#2FA7FF] transition-all duration-200 placeholder:text-[#CBD5E1]"
                                    />
                                </div>
                                <div>
                                    <label for="contact-email" class="block text-[10px] font-bold tracking-[0.15em] uppercase text-[#64748B] mb-2">Email Address <span class="text-[#F97316]">*</span></label>
                                    <input
                                        id="contact-email"
                                        x-model="form.email"
                                        type="email"
                                        required
                                        placeholder="juan@example.com"
                                        class="w-full px-4 py-3 text-sm text-[#111827] bg-[#F8FAFC] border border-[#E2E8F0] rounded-none focus:outline-none focus:ring-2 focus:ring-[#2FA7FF]/30 focus:border-[#2FA7FF] transition-all duration-200 placeholder:text-[#CBD5E1]"
                                    />
                                </div>
                            </div>

                            <!-- Subject / Topic -->
                            <div>
                                <label for="contact-subject" class="block text-[10px] font-bold tracking-[0.15em] uppercase text-[#64748B] mb-2">Subject / Topic <span class="text-[#F97316]">*</span></label>
                                <select
                                    id="contact-subject"
                                    x-model="form.subject"
                                    required
                                    class="w-full px-4 py-3 text-sm text-[#111827] bg-[#F8FAFC] border border-[#E2E8F0] rounded-none focus:outline-none focus:ring-2 focus:ring-[#2FA7FF]/30 focus:border-[#2FA7FF] transition-all duration-200 appearance-none cursor-pointer"
                                >
                                    <option value="" disabled selected>Select a topic…</option>
                                    <option value="volunteer">Volunteer Inquiry</option>
                                    <option value="partnership">Partnership / Sponsorship</option>
                                    <option value="media">Media / Press Inquiry</option>
                                    <option value="donation">Donation Question</option>
                                    <option value="program">Program Information</option>
                                    <option value="others">Others</option>
                                </select>
                            </div>

                            
                            <!-- Other Concern (conditional) -->
                            <div x-show="form.subject === 'others'" x-collapse>
                                <div>
                                    <label for="other_concern" class="block text-[10px] font-bold tracking-[0.15em] uppercase text-[#64748B] mb-2">Please specify your concern <span class="text-[#F97316]">*</span></label>
                                    <input
                                        id="other_concern"
                                        x-model="form.otherConcern"
                                        type="text"
                                        placeholder="Enter your concern"
                                        class="w-full px-4 py-3 text-sm text-[#111827] bg-[#F8FAFC] border border-[#E2E8F0] rounded-none focus:outline-none focus:ring-2 focus:ring-[#2FA7FF]/30 focus:border-[#2FA7FF] transition-all duration-200 placeholder:text-[#CBD5E1]"
                                    />
                                </div>
                            </div>

                            <!-- Phone (optional) -->
                            <div>
                                <label for="contact-phone" class="block text-[10px] font-bold tracking-[0.15em] uppercase text-[#64748B] mb-2">
                                    Phone Number <span class="text-[#94A3B8] font-normal normal-case tracking-normal">(optional)</span>
                                </label>
                                <input
                                    id="contact-phone"
                                    x-model="form.phone"
                                    type="tel"
                                    placeholder="+63 9XX XXX XXXX"
                                    class="w-full px-4 py-3 text-sm text-[#111827] bg-[#F8FAFC] border border-[#E2E8F0] rounded-none focus:outline-none focus:ring-2 focus:ring-[#2FA7FF]/30 focus:border-[#2FA7FF] transition-all duration-200 placeholder:text-[#CBD5E1]"
                                />
                            </div>

                            <!-- Message -->
                            <div>
                                <label for="contact-message" class="block text-[10px] font-bold tracking-[0.15em] uppercase text-[#64748B] mb-2">Your Message <span class="text-[#F97316]">*</span></label>
                                <textarea
                                    id="contact-message"
                                    x-model="form.message"
                                    required
                                    rows="5"
                                    placeholder="Tell us how we can help, or share your idea with us…"
                                    class="w-full px-4 py-3 text-sm text-[#111827] bg-[#F8FAFC] border border-[#E2E8F0] rounded-none focus:outline-none focus:ring-2 focus:ring-[#2FA7FF]/30 focus:border-[#2FA7FF] transition-all duration-200 placeholder:text-[#CBD5E1] resize-none"
                                ></textarea>
                                <p class="text-[10px] text-[#94A3B8] mt-1 text-right" x-text="(form.message || '').length + ' / 1000 chars'"></p>
                            </div>

                            <!-- Error message -->
                            <div x-show="error" x-cloak class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-sm px-4 py-3 text-sm text-red-600">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                                <span x-text="errorMessage"></span>
                            </div>

                            <!-- Submit -->
                            <div class="flex items-center justify-between gap-4 pt-2">
                                <p class="text-[10px] text-[#94A3B8] leading-relaxed max-w-xs">
                                    By submitting, you agree to our
                                    <a href="{{ route('terms') }}" class="text-[#2FA7FF] hover:underline">Terms</a>
                                    and acknowledge our
                                    <a href="{{ route('privacy-policy') }}" class="text-[#2FA7FF] hover:underline">Privacy Policy</a>.
                                </p>
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="inline-flex items-center justify-center gap-2 px-7 py-3 text-xs font-bold tracking-[0.15em] uppercase bg-[#132D6B] text-white hover:bg-[#2FA7FF] shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 rounded-none disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <span x-show="!loading">Send Message</span>
                                    <span x-show="loading" x-cloak class="flex items-center gap-2">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Sending…
                                    </span>
                                    <svg x-show="!loading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- MAP / LOCATION EMBED -->
<section
    x-data="{ isOnline: navigator.onLine }"
    @online.window="isOnline = true"
    @offline.window="isOnline = false"
    class="relative bg-[#0D1B2A] overflow-hidden"
>
    <div class="relative h-72 md:h-130 w-full">
        <!-- Offline Notice Overlay -->
        <div
            x-show="!isOnline"
            x-cloak
            class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-[#0D1B2A]/95 backdrop-blur-md px-6 text-center transition-all duration-300"
        >
            <div class="w-14 h-14 mb-4 rounded-full bg-[#132D6B]  flex items-center justify-center text-red-500 shadow-xl">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM12 3a9 9 0 100 18 9 9 0 000-18z" />
                </svg>
            </div>
            <h3 class="text-base sm:text-lg font-heading font-bold text-white uppercase tracking-wider mb-2">
                Map Unavailable Offline
            </h3>
            <p class="text-xs sm:text-sm text-white/70 max-w-md leading-relaxed mb-6">
                An active internet connection is required to display Google Maps. Please check your network connection and try again.
            </p>
            <!-- <div class="flex flex-wrap items-center justify-center gap-3">
                
                <button
                    type="button"
                    @click="isOnline = navigator.onLine"
                    class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest bg-[#2FA7FF]/20 hover:bg-[#2FA7FF]/30 text-white border border-[#2FA7FF]/30 transition-all rounded-none cursor-pointer"
                >
                    Retry Connection
                </button>
            </div> -->
        </div>

        {{-- Google Maps sets its own cookies, so the map loads only after consent (see the Cookie Policy) --}}
        <div
            class="absolute inset-0"
            x-data="{ allowed: window.gobikeConsent ? window.gobikeConsent.allowsThirdParty() : false }"
            @cookie-consent.window="allowed = $event.detail === 'all'"
        >
            <template x-if="allowed">
                <div class="absolute inset-0">
                    <iframe
                        title="Go Bike Location — Pangasinan, Philippines"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d244767.07891946714!2d119.86557870428943!3d15.893520736700673!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33919fc0c2e9e67b%3A0x13e2c7c7d99e9e5!2sPangasinan%2C%20Philippines!5e0!3m2!1sen!2sus!4v1700000000000!5m2!1sen!2sus"
                        class="w-full h-full grayscale opacity-80 border-0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                    ></iframe>
                    <div class="absolute inset-0 bg-[#132D6B]/20 pointer-events-none mix-blend-multiply"></div>
                </div>
            </template>

            <div x-show="!allowed" class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
                <svg class="w-10 h-10 mb-4 text-[#2FA7FF]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <h3 class="text-base sm:text-lg font-heading font-bold text-white uppercase tracking-wider mb-2">Bugallon, Pangasinan</h3>
                <p class="text-sm text-white/60 max-w-md leading-relaxed mb-6">
                    This map is provided by Google Maps, which may set its own cookies.
                    <a href="{{ route('cookie-policy') }}" class="text-[#2FA7FF] underline underline-offset-2">Learn more</a>
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <button type="button" @click="allowed = true" class="cookie-btn cookie-btn--primary">Load map</button>
                    <a href="https://www.google.com/maps/search/?api=1&amp;query=Bugallon%2C+Pangasinan%2C+Philippines" target="_blank" rel="noopener" class="cookie-btn cookie-btn--ghost">Open in Google Maps</a>
                </div>
            </div>
        </div>

        
    </div>
</section>

<!-- CTA BANNER -->
<section class="py-24 bg-[#132D6B] relative overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-[#2FA7FF]/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 rounded-full bg-[#F97316]/10 blur-3xl"></div>
    </div>
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        
        <h2 class="text-3xl md:text-5xl font-heading font-bold text-white uppercase tracking-tight mb-5">
            Ready To Make A<br/>
            <span class="text-[#F97316]">Difference?</span>
        </h2>
        <p class="text-white/70 text-base max-w-xl mx-auto leading-relaxed mb-10">
            Join hundreds of volunteers and community members who are already making an impact across Pangasinan.
        </p>
        {{-- Stacked on phones: both buttons share one width. Side by side from sm up. --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-4 w-full max-w-xs sm:max-w-none mx-auto">
            <a href="{{ url('/what-we-do') }}" class="contact-cta-primary">
                <span>See Our Programs</span>
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
            <a href="{{ url('/about') }}" class="btn-wbr btn-wbr-light">
                <span>Learn About Us</span>
                <svg class="w-5 h-5 shrink-0 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</section>

<x-slot:scripts>
    <script src="{{ asset('js/contact.js') }}"></script>
</x-slot:scripts>

</x-layout>
