<x-layout
    title="Project HATAKE | OneGoBike"
    description="Through Project HATAKE, Padyarescue Incorporated promotes communal farming as a pathway toward food security, community participation, and climate resilience in Bugallon, Pangasinan."
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
                    Project HATAKE
                </h1>

                {{-- Publication meta --}}
                <div class="py-3.5 border-y border-slate-200 text-xs sm:text-sm text-slate-600 font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <time datetime="2026-04-09">April 09, 2026</time>
                    <span class="text-slate-300 mx-1">•</span>
                    <span class="text-xs uppercase tracking-wider font-semibold text-[#16A34A]">Communal Farming &amp; Food Security</span>
                </div>

                {{-- Lead standfirst --}}
                <div class="mt-6 text-lg sm:text-xl text-slate-700 font-normal leading-relaxed italic border-l-4 border-[#16A34A] pl-4 sm:pl-5 bg-emerald-50/40 py-3 rounded-r-sm">
                    Food security remains a pressing concern for many vulnerable communities, particularly as families face rising food costs, limited livelihood opportunities, and the growing impacts of disasters and climate-related hazards.
                </div>
            </header>

            {{-- Hero Image --}}
            <figure class="mb-10">
                <div class="overflow-hidden rounded-sm bg-slate-100 shadow-sm border border-slate-100">
                    <img
                        src="{{ asset('images/whatwedo/hatake.jpg') }}"
                        alt="Project HATAKE Launch in Barangay Hacienda, Bugallon, Pangasinan"
                        class="w-full h-auto object-cover max-h-[540px]"
                        loading="eager"
                    />
                </div>
                <figcaption class="mt-2.5 text-xs sm:text-sm text-slate-500 leading-normal">
                    <span class="font-semibold text-slate-700 uppercase tracking-wider text-xs mr-1.5">PHOTO:</span>Project HATAKE launch in Barangay Hacienda, Bugallon, Pangasinan in partnership with DSWD Project LAWA at BINHI.
                </figcaption>
            </figure>

            {{-- Article Body --}}
            <div class="article-body max-w-3xl mx-auto space-y-6 text-slate-800 text-base sm:text-lg leading-[1.85] font-normal">

                <p class="first-letter:text-5xl first-letter:font-bold first-letter:font-heading first-letter:text-[#0D1B2A] first-letter:mr-2.5 first-letter:float-left first-letter:leading-none">
                    Food security remains a pressing concern for many vulnerable communities, particularly as families face rising food costs, limited livelihood opportunities, and the growing impacts of disasters and climate-related hazards. For communities that depend on agriculture and other climate-sensitive sources of income, these challenges highlight the importance of developing sustainable, community-driven approaches to food production.
                </p>

                <p>
                    Addressing food insecurity requires more than providing immediate assistance. It also means empowering people to participate in producing food, managing available resources, and building systems that can support their communities over time.
                </p>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Communal Farming as a Pathway to Resilience
                </h2>

                <p>
                    Through <strong class="text-slate-900 font-semibold">Project HATAKE</strong>, Padyarescue Incorporated promotes communal farming as a pathway toward food security, community participation, and resilience. The initiative brings together beneficiaries, volunteers, and partners to cultivate available land, encourage collective responsibility, and demonstrate how community action can contribute to more sustainable local food systems.
                </p>

                <p>
                    Launched on April 9, 2026, in Barangay Hacienda, Bugallon, Pangasinan, Project HATAKE began with identified farming areas in Main Hacienda and West Buenlag, with approximately one hectare allocated in each area. The initiative also connects with the Department of Social Welfare and Development's <strong class="text-slate-900 font-semibold">Project LAWA at BINHI</strong>, which supports community-based approaches to food security and climate resilience.
                </p>

                {{-- Pullquote --}}
                <div class="my-9 py-5 px-6 sm:px-8 border-l-4 border-[#16A34A] bg-emerald-50/50 rounded-r-sm">
                    <blockquote class="text-lg sm:text-xl font-medium text-slate-800 italic leading-relaxed">
                        &ldquo;Farmland can become more than a place for planting crops. When communities work together to cultivate and manage it, it becomes a shared resource for addressing local needs.&rdquo;
                    </blockquote>
                    <p class="mt-2 text-xs sm:text-sm font-semibold uppercase tracking-wider text-[#16A34A]">
                        — Project HATAKE Guiding Principle
                    </p>
                </div>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Farmland as a Shared Community Resource
                </h2>

                <p>
                    At the heart of Project HATAKE is the idea that land can become more than a place for planting crops. When communities work together to cultivate and manage it, farmland can become a shared resource for addressing local needs. Through communal farming, participants have opportunities to contribute their time and effort, strengthen cooperation, and take part in a collective response to food insecurity.
                </p>

                {{-- Mid-article image --}}
                <figure class="my-8">
                    <div class="overflow-hidden rounded-sm bg-slate-100 border border-slate-100">
                        <img
                            src="{{ asset('images/whatwedo/hatake2.jpg') }}"
                            alt="Project HATAKE communal farm cultivation and soil preparation"
                            class="w-full h-auto"
                            loading="lazy"
                        />
                    </div>
                    <figcaption class="mt-2.5 text-xs sm:text-sm text-slate-500 leading-normal">
                        <span class="font-semibold text-slate-700 uppercase tracking-wider text-xs mr-1.5">PHOTO:</span>Community volunteers preparing the soil in Bugallon, Pangasinan.
                    </figcaption>
                </figure>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    A Balanced Sustainability and Advocacy Model
                </h2>

                <p>
                    The initiative also adopts a sustainability and advocacy approach. Its proposed harvest-sharing arrangement allocates <strong class="text-slate-900 font-semibold">50% of the harvest for sustainability-related needs</strong> and <strong class="text-slate-900 font-semibold">50% for advocacy efforts</strong>. This model seeks to balance the immediate benefits of food production with the resources needed to sustain community activities and extend the project's impact. Its implementation depends on transparent management, clear agreements among stakeholders, and appropriate coordination with project partners.
                </p>

                <p>
                    Beyond the harvest itself, Project HATAKE promotes the importance of shared responsibility and community ownership. Sustainable farming initiatives require consistent participation, proper planning, responsible resource management, and collaboration among beneficiaries and partners. By bringing these elements together, the project aims to help communities become active participants in addressing their own challenges rather than relying solely on external assistance.
                </p>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Food Security and Disaster Resilience
                </h2>

                <p>
                    The initiative also reflects the relationship between food security and disaster resilience. Communities affected by flooding, extreme weather, and other hazards need ways to strengthen their capacity to recover and maintain access to essential resources. Community-based food production can contribute to this effort, although its long-term benefits depend on climate-appropriate farming practices, reliable access to resources, and protection against agricultural losses.
                </p>

                <p>
                    For Padyarescue Incorporated, Project HATAKE represents an opportunity to connect volunteerism with practical community development. It demonstrates how collective action can transform available resources into opportunities for food production, cooperation, and advocacy.
                </p>

                {{-- Related programs --}}
                <div class="mt-12 pt-8 border-t border-slate-200">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500 mb-4">Explore Related Programs</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ url('/what-we-do') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#16A34A] hover:text-[#16A34A] transition-colors rounded-sm">
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
