<x-layout
    title="Padyakaisipan | OneGoBike"
    description="Through Padyakaisipan, Padyarescue Incorporated extends community service to mental health awareness, psychosocial support, and emotional well-being across schools and barangays."
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
                    Padyakaisipan
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
                    Mental health is an essential part of overall well-being, yet many people continue to face emotional and psychological challenges without knowing where to seek support.
                </div>
            </header>

            {{-- Hero Image --}}
            <figure class="mb-10">
                <div class="overflow-hidden rounded-sm bg-slate-100 shadow-sm border border-slate-100">
                    <img
                        src="{{ asset('images/whatwedo/padyakaisipan.jpg') }}"
                        alt="Padyakaisipan mental health awareness and psychosocial support"
                        class="w-full h-auto object-cover max-h-[520px]"
                        loading="eager"
                    />
                </div>
                <figcaption class="mt-2.5 text-xs sm:text-sm text-slate-500 leading-normal">
                    <span class="font-semibold text-slate-700 uppercase tracking-wider text-xs mr-1.5">PHOTO:</span>Padyakaisipan — Padyarescue Incorporated volunteers conducting mental health education and psychosocial support initiatives.
                </figcaption>
            </figure>

            {{-- Article Body --}}
            <div class="article-body max-w-3xl mx-auto space-y-6 text-slate-800 text-base sm:text-lg leading-[1.85] font-normal">

                <p class="first-letter:text-5xl first-letter:font-bold first-letter:font-heading first-letter:text-[#0D1B2A] first-letter:mr-2.5 first-letter:float-left first-letter:leading-none">
                    Mental health is an essential part of overall well-being, yet many people continue to face emotional and psychological challenges without knowing where to seek support. For students, parents, and other community members experiencing personal difficulties, access to a listening ear, appropriate guidance, and professional services can make a meaningful difference.
                </p>

                <p>
                    Through <strong class="text-slate-900 font-semibold">Padyakaisipan</strong>, Padyarescue Incorporated extends its community service to mental health awareness and psychosocial support, recognizing that resilient communities must care not only for people's physical needs but also for their emotional well-being.
                </p>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Creating Safe Spaces and Breaking Stigma
                </h2>

                <p>
                    The initiative mobilizes volunteers to help create opportunities for individuals to express their concerns, feel heard, and learn about available support. By promoting mental health awareness and connecting people with appropriate resources, Padyakaisipan seeks to help reduce the stigma surrounding mental health and encourage individuals to seek assistance when they need it.
                </p>

                {{-- Pullquote --}}
                <div class="my-9 py-5 px-6 sm:px-8 border-l-4 border-[#F97316] bg-orange-50/50 rounded-r-sm">
                    <blockquote class="text-lg sm:text-xl font-medium text-slate-800 italic leading-relaxed">
                        &ldquo;Resilient communities must care not only for people's physical needs but also for their emotional well-being. Asking for help should be encouraged rather than stigmatized.&rdquo;
                    </blockquote>
                    <p class="mt-2 text-xs sm:text-sm font-semibold uppercase tracking-wider text-[#F97316]">
                        — Padyakaisipan Advocacy
                    </p>
                </div>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Reaching People Where They Are
                </h2>

                <p>
                    The initiative's work reflects the importance of reaching people where they are. Through community engagement and partnerships, volunteers can help identify opportunities for mental health education and provide appropriate support within the limits of their training. When concerns require specialized attention, individuals should be connected with qualified mental health professionals and relevant service providers.
                </p>

                {{-- Mid-article image --}}
                <figure class="my-8">
                    <div class="overflow-hidden rounded-sm bg-slate-100 border border-slate-100">
                        <img
                            src="{{ asset('images/homepage/padyakaisipan.jpeg') }}"
                            alt="Padyakaisipan volunteers and community participants"
                            class="w-full h-auto object-cover max-h-[440px]"
                            loading="lazy"
                        />
                    </div>
                    <figcaption class="mt-2.5 text-xs sm:text-sm text-slate-500 leading-normal">
                        <span class="font-semibold text-slate-700 uppercase tracking-wider text-xs mr-1.5">PHOTO:</span>Padyakaisipan volunteers facilitating peer listening and community-based mental health awareness activities.
                    </figcaption>
                </figure>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Youth Volunteers and Compassionate Communities
                </h2>

                <p>
                    Padyakaisipan also emphasizes the role of young volunteers in building more compassionate communities. Through meaningful service, volunteers can help foster environments where people are treated with dignity, emotional struggles are not dismissed, and asking for help is encouraged rather than stigmatized.
                </p>

                <p>
                    With 17 volunteers, the initiative has reached 12 schools and 2,446 students, while also reporting 29 clients served, five bicycles used, and 600 mothers reached through its activities. These figures illustrate its efforts to extend mental health-related engagement beyond a single setting and reach different members of the community.
                </p>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    A Commitment to Understanding and Care
                </h2>

                <p>
                    More than a mental health initiative, Padyakaisipan represents a commitment to listening, understanding, and connecting people to support. It recognizes that building resilience means helping individuals navigate life's challenges while ensuring that those who need additional care are not left to face their struggles alone.
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
                        <a href="{{ url('/what-we-do/programs/ronda-kalusugan') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#F97316] hover:text-[#F97316] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            Ronda Kalusugan
                        </a>
                        <a href="{{ url('/what-we-do/programs/padyakaalaman') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#132D6B] hover:text-[#132D6B] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            PadyaKaalaman
                        </a>
                        <a href="{{ url('/what-we-do/programs/hatake') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#16A34A] hover:text-[#16A34A] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            Project HATAKE
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </article>
</x-layout>
