<x-layout
    title="About Us | Go Bike"
    description="Discover the vision, mission, objectives, theory of change, and core values driving Go Bike Pangasinan's youth-led community response."
>

<!-- HERO -->
<section class="relative min-h-[460px] md:min-h-[540px] flex flex-col justify-end pt-28 pb-12 md:pb-16 overflow-hidden bg-[#0D1B2A]">
    <img
        src="{{ asset('images/whoweare/about/about.jpg') }}"
        alt="Go Bike volunteers gathered on the lawn for a group photo"
        width="1920" height="988"
        fetchpriority="high"
        class="absolute inset-0 w-full h-full object-cover object-[center_40%]"
    />
    <div class="absolute inset-0 bg-gradient-to-b from-[#0D1B2A]/80 via-[#0D1B2A]/55 to-[#0D1B2A]" aria-hidden="true"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full reveal">
        <h1 class="about-hero-title font-heading font-bold text-white uppercase max-w-3xl">
            Driven by youth.<br/>
            <span class="text-[#2FA7FF]">Powered by service.</span>
        </h1>
        <p class="text-base md:text-lg text-white/85 max-w-xl leading-relaxed">
            Go Bike started with a simple idea that can be an instrument of change. Today, we are a recognized force of community responders serving dozens of people.
        </p>
    </div>
</section>

<!-- VISION & MISSION -->
<section id="mission-vision" class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">

        <figure class="reveal">
            <img
                src="{{ asset('images/homepage/go_bike.jpg') }}"
                alt="Four Go Bike responders in blue vests standing with their bicycles in an open field"
                width="1920" height="1440"
                loading="lazy"
                class="w-full aspect-[4/3] object-cover rounded-sm"
            />
            <figcaption class="mt-3 text-sm text-[#64748B]">Go Bike responders before a community ride.</figcaption>
        </figure>

        <div class="reveal reveal-delay-1">
            <h2 class="about-h2 text-[#111827]">Vision &amp; Mission</h2>

            <div class="vm-block">
                <h3 class="vm-label">Our vision</h3>
                <p class="vm-statement">Pangasinan’s premier youth-led organization for sustainable and resilient communities.</p>
            </div>

            <div class="vm-block">
                <h3 class="vm-label">Our mission</h3>
                <p class="vm-statement">The PRI empowers youth through mobilizing volunteers and bridging partners to provide responsive services for the community.</p>
            </div>
        </div>

    </div>
</section>

<!-- OBJECTIVES -->
<section class="py-16 md:py-24 bg-[#F8FAFC] border-y border-[#E2E8F0]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16">

        @php
        $objectives = [
            ['num' => '01', 'title' => 'Disaster-resilient community',
                'desc' => 'Strengthening community preparedness and response capabilities to reduce disaster risks and protect lives.'],
            ['num' => '02', 'title' => 'Target-focused learning and skills development',
                'desc' => 'Equipping youth and volunteers with practical knowledge and skills to become capable, responsible, and empowered community leaders.'],
            ['num' => '03', 'title' => 'Responsive basic healthcare initiative',
                'desc' => 'Bringing essential health services and awareness closer to communities through accessible and community-based health programs.'],
            ['num' => '04', 'title' => 'Effective volunteer management',
                'desc' => 'Building a well-organized, committed, and capable volunteer network through proper coordination, training, and leadership development.'],
            ['num' => '05', 'title' => 'Strong multi-sectoral collaboration',
                'desc' => 'Fostering partnerships among communities, government agencies, schools, and other organizations to create sustainable and meaningful social impact.'],
        ];
        @endphp

        <div class="lg:col-span-4 reveal">
            <div class="lg:sticky lg:top-28">
                <h2 class="about-h2 text-[#111827] mb-4">Our objectives</h2>
                <p class="text-[#475569] leading-relaxed max-w-sm">What we set out to do — the five goals behind every program, training, and ride.</p>
            </div>
        </div>

        <ol class="lg:col-span-8 objectives-list">
            @foreach ($objectives as $obj)
            <li class="objective-item reveal">
                <span class="objective-num" aria-hidden="true">{{ $obj['num'] }}</span>
                <div>
                    <h3 class="objective-title">{{ $obj['title'] }}</h3>
                    <p class="text-[#475569] leading-relaxed">{{ $obj['desc'] }}</p>
                </div>
            </li>
            @endforeach
        </ol>

    </div>
</section>

<!-- THEORY OF CHANGE -->
<section id="theory-of-change" class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="max-w-3xl reveal">
            <h2 class="about-h2 text-[#111827] mb-5">Our theory of change</h2>
            <p class="toc-statement">
                When young people are empowered and equipped with the necessary training to actively engage in disaster risk reduction and management, they not only enhance their personal resilience to potential hazards but also emerge as
                <strong class="toc-highlight">catalysts for comprehensive community protection and resilience.</strong>
            </p>
        </div>

        @php
        $tocPathways = [
            [
                'title'      => 'Youth engagement',
                'activities' => [
                    'Youth Camping', 'Youth Leadership Training', 'Team Building Activity', 'Sports Activities and Balls',
                    'Other youth empowerment activities within the community',
                ],
                'output'     => 'Increased participation in community-based youth empowerment and youth development programs.',
                'outcome'    => 'The youth are empowered towards meaningful participation and community engagement.',
            ],
            [
                'title'      => 'Life-saving response',
                'activities' => [
                    'Go Biker’s Training (FA and BLS)', 'CBDRRM Training', 'CDRA Training', 'SAR Training', 'ICS Training',
                    'Emergency Drills', 'Emergency Response',
                    ['Ronda Kalusugan', 'programs.ronda-kalusugan'],
                    ['PadyaKaalaman', 'programs.padyakaalaman'],
                    ['PadyaKaisipan', 'programs.padyakaisipan'],
                    'Other volunteering programs',
                ],
                'output'     => 'Increased knowledge and skills in risk assessment, life saving, and search and rescue operations.',
                'outcome'    => 'Able youth who can offer immediate life-saving response.',
            ],
            [
                'title'      => 'Youth in governance',
                'activities' => [
                    'Participation in CBDRRMC', 'Participation in LYDC', 'Policy Advocacy', 'Youth in Governance',
                    'Youth Development Coalition – Philippines', 'Youth Forums in Public Health', 'Other events',
                ],
                'output'     => 'Increased youth participation in CBDRRMC planning, budgeting, programming, and policy implementation, and membership in larger youth coalitions that further promote meaningful youth participation in nation building.',
                'outcome'    => 'Youth are involved in the community DRRM Councils and other youth organizations related to DRRM, and are functioning in faithful accordance with existing relevant laws, policies, and regulations.',
            ],
        ];
        @endphp

        <div class="toc-flow reveal">
            @foreach ($tocPathways as $path)
            <article class="toc-path" aria-labelledby="toc-path-{{ $loop->iteration }}">
                <h3 id="toc-path-{{ $loop->iteration }}" class="toc-path-head">
                    <span class="toc-path-num">Pathway {{ $loop->iteration }}</span>
                    {{ $path['title'] }}
                </h3>

                <div class="toc-node">
                    <h4 class="toc-node-label">Activities</h4>
                    <ul class="toc-list">
                        @foreach ($path['activities'] as $activity)
                            @if (is_array($activity))
                            <li><a href="{{ route($activity[1]) }}" class="toc-link">{{ $activity[0] }}</a></li>
                            @else
                            <li>{{ $activity }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>

                <div class="toc-node">
                    <h4 class="toc-node-label">Output</h4>
                    <p class="toc-node-text">{{ $path['output'] }}</p>
                </div>

                <div class="toc-node toc-node-outcome">
                    <h4 class="toc-node-label">Outcome</h4>
                    <p class="toc-node-text">{{ $path['outcome'] }}</p>
                </div>
            </article>
            @endforeach
        </div>

        <!-- Lines from the three outcomes joining into the goal -->
        <div class="toc-merge" aria-hidden="true">
            <span></span><span></span><span></span><span class="toc-merge-bar"></span>
        </div>

        <div class="toc-goal reveal">
            <h3 class="toc-goal-label">One goal by 2030</h3>
            <p class="toc-goal-text">
                By 2030, young people in Pangasinan are more resilient to the impacts of man-made and natural disasters and more active in community-based disaster risk reduction and management.
            </p>
        </div>

        <p class="toc-abbr">
            <strong>Abbreviations:</strong>
            DRRM – Disaster Risk Reduction and Management; CBDRRM – Community-Based DRRM; CBDRRMC – Community-Based DRRM Council;
            CDRA – Climate and Disaster Risk Assessment; SAR – Search and Rescue; ICS – Incident Command System;
            FA / BLS – First Aid / Basic Life Support; LYDC – Local Youth Development Council.
        </p>

    </div>
</section>

<!-- MANTRA -->
<section class="relative overflow-hidden bg-[#0D1B2A]">
    <img
        src="{{ asset('images/homepage/story1.jpg') }}"
        alt=""
        width="1920" height="1440"
        loading="lazy"
        class="absolute inset-0 w-full h-full object-cover object-[center_45%]"
    />
    <div class="absolute inset-0 bg-[#0D1B2A]/75" aria-hidden="true"></div>

    <figure class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28 reveal">
        <blockquote class="about-mantra font-heading font-bold text-white uppercase max-w-3xl">
            <p>“Saving lives, <span class="text-[#2FA7FF]">one ride</span> at a time.”</p>
        </blockquote>
        <figcaption class="mt-6 text-white/80">Our mantra — Go Bike Project</figcaption>
    </figure>
</section>

<!-- CORE VALUES -->
<section class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl reveal">
            <h2 class="about-h2 text-[#111827] mb-4">What we stand for</h2>
            <p class="text-[#475569] leading-relaxed">More than a statement, these values shape every decision, every mission, and every act of service carried out by every Go Biker.</p>
        </div>

        @php
        $values = [
            ['label' => 'Katapatan', 'meaning' => 'Integrity',  'color' => '#2FA7FF', 'desc' => 'Upholding honesty, transparency and accountability in every action and act of service.'],
            ['label' => 'Katatagan', 'meaning' => 'Resilience', 'color' => '#F97316', 'desc' => 'Standing strong in the face of challenges and remaining committed to serving communities despite the difficulties.'],
            ['label' => 'Kahusayan', 'meaning' => 'Excellence', 'color' => '#16A34A', 'desc' => 'Striving to deliver quality service through continuous learning, dedication, and a commitment to doing better.'],
        ];
        @endphp

        <dl class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-8 lg:gap-12">
            @foreach ($values as $val)
            <div class="value-item reveal" style="--value: {{ $val['color'] }};">
                <dt>
                    <span class="value-name">{{ $val['label'] }}</span>
                    <span class="value-meaning">{{ $val['meaning'] }}</span>
                </dt>
                <dd class="mt-3 text-[#475569] leading-relaxed">{{ $val['desc'] }}</dd>
            </div>
            @endforeach
        </dl>
    </div>
</section>

<!-- CTA -->
<section class="about-cta py-16 md:py-20 bg-[#132D6B]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8 reveal">
        <div class="max-w-xl">
            <h2 class="about-h2 text-white mb-3">Be part of the story</h2>
            <p class="text-white/80 leading-relaxed">
                Whether you ride, have medical training, or simply want to serve — there is a place for you here.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ url('/what-we-do#training-plan') }}" class="btn-wbr btn-wbr-solid">
                <span>Get Training</span>
                <svg class="w-4 h-4 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
            <a href="{{ url('/org-structure') }}" class="btn-wbr btn-wbr-light">
                <span>Org Structure &amp; History</span>
            </a>
        </div>
    </div>
</section>

</x-layout>
