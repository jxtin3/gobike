<x-layout
    title="About Us | Go Bike Project"
    description="Discover the vision, mission, objectives, theory of change, and core values driving Go Bike Pangasinan's youth-led community response."
>

<!-- HERO -->
<section class="relative pt-32 pb-24 md:pt-48 md:pb-36 overflow-hidden bg-[#0D1B2A]">
    <div class="absolute inset-0 z-0">
        <img
            src="{{ asset('images/about/story-3.jpg') }}"
            alt="OneGoBike Volunteers"
            class="w-full h-full object-cover object-top opacity-30"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-[#0D1B2A]/80 via-transparent to-[#0D1B2A]"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        <!-- <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-4">
            <span class="w-2 h-2 rounded-full bg-[#F97316]"></span>
            Our Initiatives
        </span> -->
        <h1 class="text-5xl md:text-6xl lg:text-7xl font-heading font-bold text-white mb-6 uppercase tracking-tight leading-none drop-shadow-lg">
            Driven by Youth.<br/>
            <span class="text-[#2FA7FF]">Powered by Service.</span>
        </h1>
        <p class="text-base md:text-lg text-white/65 max-w-2xl mx-auto leading-relaxed">
            Go Bike started with a simple idea that can be an instrument of change. Today, we are a recognized force of community responders serving dozens of people.
        </p>
        <!-- <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
            <a href="{{ url('/org-structure') }}" class="btn-wbr btn-wbr-light text-sm px-7 py-3">
                <span>Org Structure &amp; History</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div> -->
    </div>
</section>

<!-- VISION & MISSION -->
<section id="mission-vision" class="py-20 md:py-15 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-3">Foundation</span>
            <h2 class="text-3xl md:text-4xl font-heading font-bold text-[#111827] uppercase tracking-tight">Vision &amp; Mission</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10">

            <!-- Vision -->
            <div class="group relative p-10 md:p-12 bg-[#132D6B] text-white rounded-sm overflow-hidden reveal card-lift">
                <div class="absolute top-0 right-0 w-72 h-72 bg-[#2FA7FF]/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="w-11 h-11 rounded-sm bg-white/10 flex items-center justify-center mb-6 relative z-10 group-hover:bg-[#2FA7FF]/20 transition-colors duration-300">
                    <svg class="w-5 h-5 text-[#2FA7FF]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span class="text-[10px] font-bold tracking-[0.2em] uppercase text-[#2FA7FF] mb-2 block relative z-10">Vision</span>
                <h3 class="text-2xl md:text-3xl font-heading font-bold text-white mb-4 uppercase tracking-wide relative z-10">Our Vision</h3>
                <p class="text-white/70 leading-relaxed relative z-10">
                    A highly resilient, health-conscious, and deeply connected Philippine province where the youth lead the charge in fostering Bayanihan — ensuring no community is left behind in times of need.
                </p>
            </div>

            <!-- Mission -->
            <div class="group relative p-10 md:p-12 bg-[#F8FAFC] border border-[#E2E8F0] rounded-sm overflow-hidden reveal reveal-delay-1 card-lift">
                <div class="w-11 h-11 rounded-sm bg-[#F97316]/10 flex items-center justify-center mb-6 group-hover:bg-[#F97316]/20 transition-colors duration-300">
                    <svg class="w-5 h-5 text-[#F97316]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <span class="text-[10px] font-bold tracking-[0.2em] uppercase text-[#F97316] mb-2 block">Mission</span>
                <h3 class="text-2xl md:text-3xl font-heading font-bold text-[#111827] mb-4 uppercase tracking-wide">Our Mission</h3>
                <p class="text-[#64748B] leading-relaxed">
                    To mobilize and empower the youth of Pangasinan through cycling and volunteerism — providing rapid community response, accessible health outreach, and proactive disaster preparedness initiatives for marginalized barangays.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- OBJECTIVES -->
<section class="py-10 md:py-25 bg-[#F8FAFC]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-3">What We Set Out to Do</span>
            <h2 class="text-3xl md:text-4xl font-heading font-bold text-[#111827] uppercase tracking-tight">Our Objectives</h2>
        </div>

        @php
        $objectives = [
            ['num'=>'01','title'=>'Build Rapid Response Capacity','desc'=>'Train and deploy youth cyclist-responders capable of reaching remote barangays within minutes for medical, disaster, and community emergencies.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>', 'bg_class'=>'bg-[#10B981]/10', 'hover_bg_class'=>'group-hover:bg-[#10B981]', 'text_class'=>'text-[#10B981]'],
            ['num'=>'02','title'=>'Expand Health Outreach','desc'=>'Partner with health institutions to deliver free medical consultations, medicines, and wellness programs to underserved communities throughout Pangasinan.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>', 'bg_class'=>'bg-[#EF4444]/10', 'hover_bg_class'=>'group-hover:bg-[#EF4444]', 'text_class'=>'text-[#EF4444]'],
            ['num'=>'03','title'=>'Strengthen Disaster Preparedness','desc'=>'Conduct regular community drills, establish bicycle-based rescue relay systems, and pre-position emergency supply caches in flood-prone areas.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>', 'bg_class'=>'bg-[#F59E0B]/10', 'hover_bg_class'=>'group-hover:bg-[#F59E0B]', 'text_class'=>'text-[#F59E0B]'],
            ['num'=>'04','title'=>'Cultivate Youth Leadership','desc'=>'Provide structured training, mentorship, and leadership pathways for young volunteers to grow from responders into community leaders and advocates.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>', 'bg_class'=>'bg-[#8B5CF6]/10', 'hover_bg_class'=>'group-hover:bg-[#8B5CF6]', 'text_class'=>'text-[#8B5CF6]'],
            ['num'=>'05','title'=>'Foster LGU & NGO Partnerships','desc'=>'Deepen formal agreements with local government units, national agencies, and civil society organizations to amplify program reach and sustainability.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/>', 'bg_class'=>'bg-[#6B7280]/10', 'hover_bg_class'=>'group-hover:bg-[#6B7280]', 'text_class'=>'text-[#6B7280]'],
            ['num'=>'06','title'=>'Promote Environmental Stewardship','desc'=>'Integrate eco-advocacy into every program — championing zero-carbon mobility, tree planting, and coastal clean-ups across Pangasinan communities.','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>', 'bg_class'=>'bg-[#3B82F6]/10', 'hover_bg_class'=>'group-hover:bg-[#3B82F6]', 'text_class'=>'text-[#3B82F6]'],
        ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($objectives as $i => $obj)
            <div class="group relative p-8 bg-white border border-[#E2E8F0] rounded-sm card-lift reveal">
                <div class="flex items-start gap-4 mb-5">
                    <div class="w-10 h-10 rounded-sm {{ $obj['bg_class'] }} flex items-center justify-center shrink-0 {{ $obj['hover_bg_class'] }} transition-colors duration-300">
                        <svg class="w-5 h-5 {{ $obj['text_class'] }} group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            {!! $obj['icon'] !!}
                        </svg>
                    </div>
                    <!-- <span class="text-4xl font-heading font-black text-[#F97316]/10 leading-none select-none">{{ $obj['num'] }}</span> -->
                </div>
                <h3 class="text-base font-heading font-bold text-[#111827] uppercase tracking-wide mb-2">{{ $obj['title'] }}</h3>
                <p class="text-sm text-[#64748B] leading-relaxed">{{ $obj['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- THEORY OF CHANGE -->
<section class="py-10 md:py-10 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">

            <div class="reveal">
                <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-4">How We Create Impact</span>
                <h2 class="text-3xl md:text-4xl font-heading font-bold text-[#111827] uppercase tracking-tight mb-6">Theory of Change</h2>
                <p class="text-[#64748B] leading-relaxed mb-6">
                    We believe sustainable change begins with empowered young people. By equipping youth volunteers with the skills, networks, and resources to serve their communities, we create a ripple effect that strengthens entire provinces.
                </p>
                <p class="text-[#64748B] leading-relaxed">
                    Every training session, every barangay visit, and every life touched contributes to a more resilient, health-conscious Pangasinan — where communities look after one another without waiting for external intervention.
                </p>
            </div>

            <div class="space-y-0 reveal reveal-delay-1">
                @php
                $toc = [
                    ['label'=>'Inputs',    'color'=>'#F97316','items'=>['Youth volunteers','Partner LGUs & NGOs','Bicycles & equipment','Training curricula']],
                    ['label'=>'Activities','color'=>'#132D6B','items'=>['Community response drills','Health outreach missions','Leadership development','Disaster preparedness training']],
                    ['label'=>'Outputs',  'color'=>'#2FA7FF','items'=>['Trained volunteer responders','Served barangays','Medical consultations','Relief goods distributed']],
                    ['label'=>'Outcomes', 'color'=>'#16A34A','items'=>['Faster emergency response','Improved community health','Youth leadership growth','Stronger Bayanihan culture']],
                    ['label'=>'Impact',   'color'=>'#F97316','items'=>['A resilient, self-sustaining Pangasinan where no community is left behind']],
                ];
                @endphp

                @foreach ($toc as $ti => $step)
                <div class="flex gap-4 relative">
                    @if (!$loop->last)
                    <div class="absolute left-[18px] top-10 bottom-0 w-0.5" style="background: linear-gradient(to bottom, {{ $step['color'] }}40, transparent)"></div>
                    @endif
                    <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold font-heading z-10" style="background-color: {{ $step['color'] }}">
                        {{ $ti + 1 }}
                    </div>
                    <div class="pb-8 flex-1">
                        <h4 class="text-sm font-heading font-bold uppercase tracking-widest mb-2" style="color: {{ $step['color'] }}">{{ $step['label'] }}</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($step['items'] as $item)
                            <span class="text-xs px-3 py-1.5 rounded-sm border font-medium text-[#374151]" style="border-color: {{ $step['color'] }}25; background-color: {{ $step['color'] }}08">{{ $item }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>
</section>

<!-- MANTRA -->
<section class="py-5 md:py-20 bg-white relative overflow-hidden flex items-center justify-center border-t border-b border-gray-100">
    <div class="absolute inset-0 bg-noise opacity-[0.02] pointer-events-none"></div>
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        <span class="inline-block text-[10px] font-bold tracking-[0.3em] uppercase text-[#F97316] mb-6">Our Core Mantra</span>
        <blockquote class="text-4xl md:text-5xl lg:text-6xl font-heading font-black text-[#111827] uppercase tracking-tighter leading-[1.1]">
            " Saving Lives <br class="hidden sm:block" />
            <span class="text-[#2FA7FF] inline-block mt-2">One Ride</span> At A Time "
        </blockquote>
        <div class="w-16 h-1 bg-[#F97316] mx-auto mt-10 mb-6"></div>
        <p class="text-[#64748B] text-lg font-medium tracking-wide">— Go Bike Project</p>
    </div>
</section>

<!-- CORE VALUES -->
<section class="py-20 md:py-28 bg-[#0D1B2A] relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#132D6B]/40 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-[#F97316]/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <h2 class="text-3xl md:text-4xl font-heading font-bold text-white uppercase tracking-tight mb-4">What We Stand For</h2>
            <p class="text-white/50 max-w-xl mx-auto text-sm leading-relaxed">More than a statement, these values shape every decision, every mission, and every act of service carried out by every Go Biker.</p>
        </div>

        @php
        $values = [
            ['letter'=>'B','label'=>'Bayanihan',    'color'=>'#2FA7FF','desc'=>'We embody the Filipino spirit of communal unity — no barangay faces a crisis alone when OneGoBike is present.'],
            ['letter'=>'A','label'=>'Accountability','color'=>'#F97316','desc'=>'We own our actions and outcomes, holding ourselves to the highest standards of transparency in service.'],
            ['letter'=>'R','label'=>'Resilience',   'color'=>'#16A34A','desc'=>'We push through adversity with grit, adapting to challenges without losing sight of our mission.'],
            ['letter'=>'E','label'=>'Empathy',      'color'=>'#2FA7FF','desc'=>'We listen before we act. Every community has unique needs; we lead with compassion and respect.'],
            ['letter'=>'S','label'=>'Service',      'color'=>'#F97316','desc'=>'Service is not a task — it is our identity. We give without expectation, driven purely by love of community.'],
        ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($values as $i => $val)
            <div class="group relative p-8 rounded-sm border border-white/[0.07] bg-white/[0.03] hover:bg-white/[0.06] hover:border-white/[0.12] transition-all duration-300 card-lift-dark reveal">
                <div class="flex items-center gap-4 mb-5">
                    <span class="text-5xl font-heading font-black leading-none select-none" style="color: {{ $val['color'] }}25">{{ $val['letter'] }}</span>
                    <div>
                        <p class="text-[9px] font-bold tracking-[0.2em] uppercase mb-0.5" style="color: {{ $val['color'] }}">Core Value</p>
                        <h3 class="text-lg font-heading font-bold text-white uppercase tracking-wide">{{ $val['label'] }}</h3>
                    </div>
                </div>
                <div class="w-8 h-0.5 mb-4 rounded-full" style="background-color: {{ $val['color'] }}"></div>
                <p class="text-sm text-white/55 leading-relaxed">{{ $val['desc'] }}</p>
            </div>
            @endforeach

            <div class="relative p-8 rounded-sm border border-dashed border-[#F97316]/20 bg-[#F97316]/5 flex flex-col items-center justify-center text-center reveal">
                <p class="text-white/40 text-sm mb-4">Ready to live these values?</p>
                <a href="{{ url('/volunteer') }}" class="btn-wbr btn-wbr-orange text-xs px-6 py-2.5">
                    <span>Join the Ride</span>
                    <svg class="w-4 h-4 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-24 bg-white text-center px-4">
    <div class="max-w-3xl mx-auto reveal">
        <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.22em] uppercase text-[#F97316] mb-4">Take the Next Step</span>
        <h2 class="text-3xl md:text-5xl font-heading font-bold text-[#111827] mb-5 uppercase tracking-tight">Be Part of the Story</h2>
        <p class="text-[#64748B] mb-10 text-base max-w-xl mx-auto leading-relaxed">
            Whether you ride, have medical training, or simply want to serve — there is a place for you here. Explore our organizations Structure &amp; History.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <!-- <a href="{{ url('/volunteer') }}" class="btn-wbr btn-wbr-orange">
                <span>Join Our Team</span>
                <svg class="w-5 h-5 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a> -->
            <a href="{{ url('/org-structure') }}" class="btn-wbr btn-wbr-dark">
                <span>Org Structure</span>
                <svg class="w-5 h-5 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</section>

</x-layout>

