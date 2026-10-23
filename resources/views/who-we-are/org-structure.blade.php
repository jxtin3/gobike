<x-layout
    title="Org Structure & History | Go Bike"
    description="Explore the organizational structure, leadership team, and history of Go Bike Project — a youth-led community responder organization."
>

<!-- HERO -->
<section class="relative min-h-[460px] md:min-h-[500px] flex flex-col justify-center pt-28 pb-16 md:pt-32 md:pb-20 overflow-hidden bg-[#0D1B2A]">
    <div class="absolute inset-0 z-0">
        <img
            src="{{ asset('images/whoweare/orgstructure/about.jpg') }}"
            alt="OneGoBike Volunteers"
            class="w-full h-full object-cover object-[center_35%] opacity-45"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-[#0D1B2A]/80 via-[#0D1B2A]/40 to-[#0D1B2A]/55"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="reveal">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white mb-5 uppercase tracking-tight leading-tight drop-shadow-lg">
                Organization Structure<br/><span class="text-[#2FA7FF]">&amp; History</span>
            </h1>
            <p class="text-base text-white/60 max-w-2xl leading-relaxed">
                From a small idea of volunteerism to a province-wide force of youth volunteers — this is the story and structure of Go Bike.
            </p>
        </div>
    </div>
</section>

<!-- ORG STRUCTURE -->
{{-- Hidden for now (org structure + departments); remove this Blade comment to show it again.
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
--}}

<!-- HISTORY / JOURNEY -->
@php
$journeyIcons = [
    'flag'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/>',
    'heart'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>',
    'building' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/>',
    'users'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>',
    'trophy'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0"/>',
    'sprout'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 20h10M10 20c5.5-2.5.8-6.4 3-10M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8ZM14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2Z"/>',
    'badge'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>',
    'target'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-4.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0-3.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"/>',
];

// Facts are taken from the program pages (what-we-do/programs/*), the homepage news and the linked articles — keep them in sync.
$journey = [
    [
        'date' => 'Mar 16, 2019', 'datetime' => '2019-03-16', 'year' => '2019', 'icon' => 'flag',
        'title' => 'It starts in Umanday',
        'text'  => 'In Barangay Umanday, Bugallon, Edren Llanillo, Sheena Mae Quiles and Nikko De Mesa start the Go Bike Project — young people trained and organized as community responders, getting around by bicycle.',
        'links' => [['label' => 'Read the Go Bike story', 'href' => route('programs.gobike')]],
    ],
    [
        'date' => 'Jan 2020', 'datetime' => '2020-01', 'year' => '2020', 'icon' => 'heart',
        'title' => 'Ronda Kalusugan begins',
        'text'  => 'About 10 Go Bikers start health rounds — blood pressure and blood sugar checks for senior citizens. When COVID-19 restricted movement that year, older residents still needed those check-ups.',
        'links' => [['label' => 'About Ronda Kalusugan', 'href' => route('programs.ronda-kalusugan')]],
    ],
    [
        'date' => 'Mar 25, 2021', 'datetime' => '2021-03-25', 'year' => '2021', 'icon' => 'building',
        'title' => 'Padyarescue Incorporated is registered',
        'text'  => 'Registered with the Securities and Exchange Commission, Padyarescue Incorporated gives Go Bike an institutional home for partnerships, resources and new programs.',
    ],
    [
        'date' => '2021', 'datetime' => '2021', 'year' => '2021', 'icon' => 'users',
        'title' => 'Every barangay in Bugallon',
        'text'  => 'A Galing Pook Awards program profile documents the volunteer network reaching all 24 barangays of Bugallon.',
    ],
    [
        'date' => '2024', 'datetime' => '2024', 'year' => '2024', 'icon' => 'trophy',
        'title' => 'Healthy Aging Prize for Asian Innovation',
        'text'  => 'The Go Bike Project–Ronda Kalusugan Program wins the Grand Prize in the Community-Based Initiatives category, for bringing basic health checks closer to older adults by bike.',
        'links' => [['label' => 'See the 2024 winners', 'href' => 'https://ahwin.org/2024-hapi-winners/', 'external' => true]],
    ],
    [
        'date' => 'Dec 5, 2024', 'datetime' => '2024-12-05', 'year' => '2024', 'icon' => 'badge',
        'title' => 'Philippine Resilience Awards',
        'text'  => 'Co-founder Edren Llanillo, Executive Director of Padyarescue Incorporated, is honored in the youth category of the Philippine Resilience Awards 2024, which recognized women and youth climate champions.',
        'links' => [['label' => 'Read the Daily Tribune story', 'href' => 'https://tribune.net.ph/2024/12/05/philippine-resilience-awards-2024-honors-women-youth-climate-champions', 'external' => true]],
    ],
    [
        'date' => 'Apr 9, 2026', 'datetime' => '2026-04-09', 'year' => '2026', 'icon' => 'sprout',
        'title' => 'Project HATAKE launches',
        'text'  => 'Community farming comes to Barangay Hacienda, Bugallon, starting with about a hectare each in Main Hacienda and West Buenlag.',
        'links' => [['label' => 'About Project HATAKE', 'href' => route('programs.hatake')]],
    ],
    [
        'date' => 'Next stop', 'datetime' => null, 'year' => '2030', 'icon' => 'target', 'next' => true,
        'title' => 'The 2030 goal',
        'text'  => 'Young people in Pangasinan more resilient to man-made and natural disasters, and more active in community-based disaster risk reduction and management.',
        'links' => [['label' => 'See our theory of change', 'href' => url('/about#theory-of-change')]],
    ],
];
@endphp
<section
    id="journey"
    class="journey py-16 md:py-24 bg-white"
    x-data="{
        reached: 0,
        current: 0,
        total: {{ count($journey) }},
        reduce: false,
        init() {
            this.reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let ticking = false;
            const onScroll = () => {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(() => { this.update(); ticking = false; });
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onScroll, { passive: true });
            this.update();
        },
        update() {
            const nodes = this.$refs.track.querySelectorAll('.journey-node');
            if (!nodes.length) return;
            const line = window.innerHeight * 0.55;
            const centers = [...nodes].map((node) => { const r = node.getBoundingClientRect(); return r.top + r.height / 2; });
            const first = centers[0];
            const last = centers[centers.length - 1];
            const passed = centers.filter((c) => c <= line).length;
            const progress = Math.min(Math.max((line - first) / (last - first), 0), 1);
            this.$refs.track.style.setProperty('--rail-h', (last - first) + 'px');
            this.$refs.track.style.setProperty('--fill', this.reduce ? 1 : progress);
            this.$root.style.setProperty('--p', progress);
            this.reached = this.reduce ? this.total : passed;
            this.current = Math.max(passed - 1, 0);
        },
    }"
>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="journey-layout">

            <div class="journey-aside">
                <h2 class="journey-title">Our journey</h2>
                <p class="journey-intro">From about 70 volunteers in one barangay to a growing network across Pangasinan.</p>

                <div class="journey-tracker" aria-hidden="true">
                    <div class="journey-tracker-years">
                        @foreach ($journey as $i => $item)
                        <span class="journey-tracker-year {{ $i === 0 ? 'is-current' : '' }}" :class="{ 'is-current': current === {{ $i }} }">{{ $item['year'] }}</span>
                        @endforeach
                    </div>
                    <p class="journey-tracker-count">Milestone <span x-text="current + 1">1</span> of {{ count($journey) }}</p>
                    <span class="journey-tracker-bar"><span></span></span>
                </div>
            </div>

            <div class="journey-track" x-ref="track">
                <span class="journey-rail" aria-hidden="true"><span class="journey-rail-fill"></span></span>

                <ol class="journey-list">
                    @foreach ($journey as $i => $item)
                    <li
                        class="journey-item reveal {{ !empty($item['next']) ? 'journey-item-next' : '' }}"
                        :class="{ 'is-reached': reached > {{ $i }}, 'is-current': current === {{ $i }} && reached > 0 }"
                    >
                        <span class="journey-node" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">{!! $journeyIcons[$item['icon']] !!}</svg>
                        </span>

                        <article class="journey-card">
                            <p class="journey-date">
                                @if ($item['datetime'])
                                <time datetime="{{ $item['datetime'] }}">{{ $item['date'] }}</time>
                                @else
                                {{ $item['date'] }}
                                @endif
                            </p>
                            <h3 class="journey-card-title">{{ $item['title'] }}</h3>
                            <p class="journey-text">{{ $item['text'] }}</p>

                            @if (!empty($item['links']))
                            <div class="journey-links">
                                @foreach ($item['links'] as $link)
                                <a
                                    href="{{ $link['href'] }}"
                                    class="journey-link"
                                    @if (!empty($link['external'])) target="_blank" rel="noopener" @endif
                                >
                                    {{ $link['label'] }}@if (!empty($link['external']))<span class="sr-only"> (opens in a new tab)</span>@endif
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </article>
                    </li>
                    @endforeach
                </ol>
            </div>

        </div>
    </div>
</section>

<!-- CTA -->
<section class="relative py-40 text-center px-4 overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img src="{{ asset('images/whoweare/orgstructure/training.JPG') }}" alt="training of volunters" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-[#132D6B]/20 to-[#0D1B2A]/80"></div>
    </div>
    <div class="relative z-10 max-w-2xl mx-auto reveal">
        <h2 class="text-4xl md:text-4xl font-heading font-bold text-white mb-5 uppercase tracking-tight">Join the Movement</h2>
        <p class="text-white/80 mb-10 leading-relaxed">The next chapter of Go Bike's story is waiting to be written — and it could be yours.</p>

        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="{{ url('/volunteer') }}" class="btn-wbr btn-wbr-light">
                <span>Volunteer with us</span>
                <svg class="w-5 h-5 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>

    </div>
</section>

</x-layout>
