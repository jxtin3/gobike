<x-layout
    title="Org Structure & History | Go Bike Project"
    description="Explore the organizational structure, leadership team, and history of Go Bike Project — a youth-led community responder organization."
>

<!-- HERO -->
<section class="relative pt-32 pb-20 md:pt-44 md:pb-28 overflow-hidden bg-[#0D1B2A]">
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-gradient-to-br from-[#132D6B]/60 via-[#0D1B2A] to-[#0D1B2A]"></div>
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-[#2FA7FF]/5 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white mb-5 uppercase tracking-tight leading-none">
                Organization Structure<br/><span class="text-[#2FA7FF]">&amp; History</span>
            </h1>
            <p class="text-base text-white/60 max-w-2xl leading-relaxed">
                From a small idea of volunteerism to a province-wide force of youth volunteers — this is the story and structure of Go Bike.
            </p>
        </div>
    </div>
</section>

<!-- ORG STRUCTURE -->
<section class="py-20 md:py-28 bg-[#F8FAFC]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-3">Leadership</span>
            <h2 class="text-3xl md:text-4xl font-heading font-bold text-[#111827] uppercase tracking-tight">Organizational Structure</h2>
        </div>

        <!-- Board / Executive -->
        <div class="mb-12">
            <h3 class="text-[11px] font-bold tracking-[0.2em] uppercase text-[#132D6B] mb-6 text-center">Executive Board</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-4xl mx-auto">
                @php
                $board = [
                    ['role'=>'Founder & Executive Director','name'=>'Hon. Edren M. LLanilo','badge'=>'Founder'],
                    ['role'=>'Vice President, Operations','name'=>'Maria Santos','badge'=>'Operations'],
                    ['role'=>'Secretary General','name'=>'Jonathan Cruz','badge'=>'Governance'],
                ];
                @endphp
                @foreach ($board as $member)
                <div class="text-center p-8 bg-white border border-[#E2E8F0] rounded-sm card-lift reveal">
                    <div class="w-16 h-16 rounded-full bg-[#132D6B] flex items-center justify-center text-white font-heading font-bold text-xl mx-auto mb-4">
                        {{ strtoupper(substr($member['name'], 0, 1)) }}
                    </div>
                    <h4 class="font-heading font-bold text-[#111827] uppercase tracking-wide text-sm mb-1">{{ $member['name'] }}</h4>
                    <p class="text-xs text-[#64748B]">{{ $member['role'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Departments -->
        <div>
            <h3 class="text-[11px] font-bold tracking-[0.2em] uppercase text-[#132D6B] mb-6 text-center">Departments</h3>
            @php
            $departments = [
                ['name'=>'Emergency Response Unit','head'=>'Rapid deployment of trained cyclist-responders to medical, disaster, and community emergencies.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>','color'=>'#F97316'],
                ['name'=>'Health Outreach Division','head'=>'Coordinating free clinics, medicine distribution, and health literacy campaigns across barangays.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>','color'=>'#2FA7FF'],
                ['name'=>'Training & Development','head'=>'Designing and delivering volunteer curricula, leadership programs, and certification pathways.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>','color'=>'#16A34A'],
                ['name'=>'Partnerships & Advocacy','head'=>'Managing LGU agreements, NGO collaborations, donor relations, and public communications.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46"/>','color'=>'#132D6B'],
            ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                @foreach ($departments as $dept)
                <div class="group flex gap-5 p-7 bg-white border border-[#E2E8F0] rounded-sm card-lift reveal">
                    <div class="w-10 h-10 rounded-sm flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform duration-300" style="background-color: {{ $dept['color'] }}15">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="{{ $dept['color'] }}">
                            {!! $dept['icon'] !!}
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-heading font-bold text-[#111827] uppercase tracking-wide text-sm mb-1.5">{{ $dept['name'] }}</h4>
                        <p class="text-sm text-[#64748B] leading-relaxed">{{ $dept['head'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<!-- HISTORY / TIMELINE -->
<section class="py-20 md:py-28 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-3">The Journey</span>
            <h2 class="text-3xl md:text-4xl font-heading font-bold text-[#111827] uppercase tracking-tight">How It Started</h2>
        </div>

        <div class="relative">
            <div class="hidden md:block absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-0.5 bg-gradient-to-b from-[#F97316] via-[#132D6B]/20 to-transparent"></div>

            @php
            $timeline = [
                ['year'=>'2019','title'=>'The First Ride','desc'=>'A small group of passionate cyclists in Dagupan City began delivering basic first aid and supplies to remote areas unreachable by four-wheeled vehicles.','align'=>'left'],
                ['year'=>'2021','title'=>'Pandemic Response','desc'=>'During the height of COVID-19, Go Bike transformed into a critical lifeline — distributing medicines and relief goods when public transportation was halted.','align'=>'right'],
                ['year'=>'2023','title'=>'Official Accreditation','desc'=>'Recognized by the DILG and partnered with local LGUs, formalizing our status as an essential youth-led disaster response and health outreach organization.','align'=>'left'],
                ['year'=>'2025','title'=>'Provincial Expansion','desc'=>'Now operating across barangays throughout the province, pushing the boundaries of what community service looks like in the modern era.','align'=>'right'],
            ];
            @endphp

            <div class="space-y-12">
                @foreach ($timeline as $idx => $item)
                <div class="relative md:w-1/2 {{ $item['align'] === 'left' ? 'md:pr-12 md:text-right' : 'md:pl-12 md:ml-auto' }} reveal pl-8 border-l-2 border-[#132D6B]/10 md:border-none md:pl-0">
                    <div class="absolute top-3 -left-[9px] md:top-1/2 md:-translate-y-1/2 {{ $item['align'] === 'left' ? 'md:-right-[7px] md:left-auto' : 'md:-left-[7px]' }} w-3.5 h-3.5 rounded-full bg-[#F97316] ring-4 ring-white z-10"></div>
                    <div class="bg-[#F8FAFC] border border-[#E2E8F0] p-6 md:p-8 rounded-sm card-lift relative overflow-hidden">
                        <span class="text-5xl font-heading font-black text-[#132D6B]/8 absolute top-3 right-5 leading-none select-none">{{ $item['year'] }}</span>
                        <h3 class="text-lg font-heading font-bold text-[#111827] mb-2 relative z-10">{{ $item['title'] }}</h3>
                        <p class="text-sm text-[#64748B] leading-relaxed relative z-10">{{ $item['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="relative py-40 text-center px-4 overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img src="{{ asset('images/1.jpg') }}" alt="volunteers background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-[#132D6B]/20 to-[#0D1B2A]/80"></div>
    </div>
    <div class="relative z-10 max-w-2xl mx-auto reveal">
        <h2 class="text-4xl md:text-4xl font-heading font-bold text-white mb-5 uppercase tracking-tight">Join the Movement</h2>
        <p class="text-white/80 mb-10 leading-relaxed">The next chapter of Go Bike's story is waiting to be written — and it could be yours.</p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="{{ url('/volunteer') }}" class="btn-wbr btn-wbr-orange">
                <span>Volunteer Now</span>
                <svg class="w-5 h-5 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
            <!-- <a href="{{ url('/about') }}" class="btn-wbr btn-wbr-dark">
                <span>Back to About</span>
            </a> -->
        </div>
    </div>
</section>

</x-layout>
