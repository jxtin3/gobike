<x-layout
    title="Ronda Kalusugan | OneGoBike"
    description="Bringing community-based blood pressure monitoring, vital signs checks, and healthcare referrals directly to elderly and vulnerable residents."
    :show-chatbot="false"
>
    {{-- Force solid dark header on this clean white page --}}
    <x-slot:head>
        <style>
            #site-header.site-header {
                background-color: #0D1B2A !important;
                backdrop-filter: blur(16px) !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
            }
            .gobot-widget { display: none !important; }
        </style>
    </x-slot:head>

    <article class="bg-white text-slate-900 min-h-screen pt-24 sm:pt-28 md:pt-36 pb-20 md:pb-28">

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Article Header --}}
            <header class="mb-10">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-heading font-bold text-[#0D1B2A] tracking-tight leading-[1.18] mb-5">
                    Ronda Kalusugan
                </h1>

                {{-- Publication meta --}}
                <div class="py-3.5 border-y border-slate-200 text-xs sm:text-sm text-slate-600 font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F97316]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <time datetime="{{ date('Y-m-d') }}">{{ date('F d, Y') }}</time>
                </div>

                {{-- Lead standfirst --}}
                <div class="mt-6 text-lg sm:text-xl text-slate-700 font-normal leading-relaxed italic border-l-4 border-[#2FA7FF] pl-4 sm:pl-5 bg-slate-50/60 py-3 rounded-r-sm">
                    Ronda Kalusugan is one of the most meaningful ways the Go Bike Project extends its service beyond disaster response, recognizing that community resilience includes caring for people's everyday health needs.
                </div>
            </header>

            {{-- Hero Image --}}
            <figure class="mb-10">
                <div class="overflow-hidden rounded-sm bg-slate-100 shadow-sm border border-slate-100">
                    <img
                        src="{{ asset('images/whatwedo/ronda.jpg') }}"
                        alt="Ronda Kalusugan volunteers conducting community health rounds"
                        class="w-full h-auto object-cover max-h-[520px]"
                        loading="eager"
                    />
                </div>
                <figcaption class="mt-2.5 text-xs sm:text-sm text-slate-500 leading-normal">
                    <span class="font-semibold text-slate-700 uppercase tracking-wider text-xs mr-1.5">PHOTO:</span>Ronda Kalusugan — Go Bike volunteers bringing blood pressure monitoring and basic health checks directly to community residents.
                </figcaption>
            </figure>

            {{-- Article Body --}}
            <div class="article-body max-w-3xl mx-auto space-y-6 text-slate-800 text-base sm:text-lg leading-[1.85] font-normal">

                <p class="first-letter:text-5xl first-letter:font-bold first-letter:font-heading first-letter:text-[#0D1B2A] first-letter:mr-2.5 first-letter:float-left first-letter:leading-none">
                    For many elderly Filipinos and people living with hypertension and diabetes, maintaining good health requires regular monitoring, consistent medical care, and access to reliable health information. However, transportation difficulties, financial constraints, and limited access to healthcare services can make these responsibilities challenging.
                </p>

                <p>
                    Health risks do not only arise during emergencies. They are part of everyday life—and regular monitoring, health education, and timely referral can make a profound difference.
                </p>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Bringing Healthcare Closer to Households
                </h2>

                <p>
                    Through <strong class="text-slate-900 font-semibold">Ronda Kalusugan</strong>, Go Bike Project brings basic health monitoring closer to the communities it serves. Launched in January 2020, the initiative began with approximately 10 Go Bikers who sought to make health services more accessible, particularly for senior citizens and residents managing chronic conditions.
                </p>

                {{-- Pullquote --}}
                <div class="my-9 py-5 px-6 sm:px-8 border-l-4 border-[#F97316] bg-orange-50/50 rounded-r-sm">
                    <blockquote class="text-lg sm:text-xl font-medium text-slate-800 italic leading-relaxed">
                        &ldquo;Health risks do not only arise during emergencies. They are part of everyday life—and regular monitoring, health education, and timely referral can make a difference.&rdquo;
                    </blockquote>
                    <p class="mt-2 text-xs sm:text-sm font-semibold uppercase tracking-wider text-[#F97316]">
                        — Ronda Kalusugan Program
                    </p>
                </div>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Vital Signs Monitoring and Health Education
                </h2>

                <p>
                    Go Bikers conduct community-based blood pressure monitoring and, where appropriate, random blood sugar checks. They help residents record their readings, understand the importance of regular monitoring, and seek professional consultation when necessary.
                </p>

                <p>
                    Through health education, they also encourage residents to follow their healthcare providers' advice, maintain healthy habits, and attend scheduled checkups.
                </p>

                {{-- Mid-article image --}}
                <figure class="my-8">
                    <div class="overflow-hidden rounded-sm bg-slate-100 border border-slate-100">
                        <img
                            src="{{ asset('images/whatwedo/programs/ronda_kalusugan.jpg') }}"
                            alt="Go Bike volunteers on health rounds"
                            class="w-full h-auto object-cover max-h-[440px]"
                            loading="lazy"
                        />
                    </div>
                    <figcaption class="mt-2.5 text-xs sm:text-sm text-slate-500 leading-normal">
                        <span class="font-semibold text-slate-700 uppercase tracking-wider text-xs mr-1.5">PHOTO:</span>Ronda Kalusugan volunteers conducting door-to-door vital checks for senior citizens.
                    </figcaption>
                </figure>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Connecting Communities with Care
                </h2>

                <p>
                    Beyond monitoring, Ronda Kalusugan helps connect residents with barangay health workers and local health facilities. When readings are concerning, volunteers can facilitate appropriate referrals rather than attempting to diagnose or treat medical conditions themselves.
                </p>

                <p>
                    More than a health outreach activity, Ronda Kalusugan reflects Go Bike Project's belief that community resilience includes caring for people's everyday health needs. By combining volunteerism, health awareness, and collaboration with healthcare providers, Go Bikers help bridge the gap between communities and essential health services.
                </p>

                {{-- Related programs --}}
                <div class="mt-12 pt-8 border-t border-slate-200">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500 mb-4">Explore Related Programs</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ url('/what-we-do') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#F97316] hover:text-[#F97316] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            All Programs
                        </a>
                        <a href="{{ url('/what-we-do/programs/gobike') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#2FA7FF] hover:text-[#2FA7FF] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            Go Bike Project
                        </a>
                        <a href="{{ url('/what-we-do/programs/alwar') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#132D6B] hover:text-[#132D6B] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            ALWAR
                        </a>
                        <a href="{{ url('/what-we-do/programs/padyakaalaman') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#132D6B] hover:text-[#132D6B] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            PadyaKaalaman
                        </a>
                        <a href="{{ url('/what-we-do/programs/padyakaisipan') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#2FA7FF] hover:text-[#2FA7FF] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            Padyakaisipan
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </article>
</x-layout>
