<x-layout
    title="Go Bike Project"
    description="Mobilizing volunteers, responders, and community leaders to strengthen health, preparedness, and community resilience."
>
     <!-- SECTION 1 — HERO -->
<section
    id="hero"
    class="relative h-[80vh] min-h-[650px] flex flex-col justify-center overflow-hidden"
    aria-labelledby="hero-headline"
>
    <!-- bg video -->
    <div class="absolute inset-0 z-0">
        <video
            autoplay
            loop
            muted
            playsinline
            class="w-full h-full object-cover object-center"
            id="hero-bg-video"
        >
            <source src="{{ asset('videos/main-video.mp4') }}" type="video/mp4">
        </video>
    </div>

    <!--overlay fade -->
    <div class="hero-overlay absolute inset-0 z-10"></div>

    <!-- Subtle grain -->
    <div class="absolute inset-0 z-10 opacity-[0.025] bg-noise" aria-hidden="true"></div>

    <!-- Bottom fade for smooth transition into next section -->
    <div class="absolute bottom-0 left-0 right-0 h-50 z-10 bg-gradient-to-t from-[#111827]/60 to-transparent" aria-hidden="true"></div>

    <!-- Content -->
    <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="max-w-3xl">

            <!-- Main headline -->
            <h1
                id="hero-headline"
                class="text-hero-title font-heading font-bold text-white mb-5 reveal"
            >
                Youth-Led<br/>
                <span class="text-[#2FA7FF]">Community</span><br/>
                Responders

                <!-- Saving lives <br/>
                <span class="text-[#2FA7FF]">One Ride</span><br/>
                At A Time -->
            </h1>

            <!-- Sub-headline -->
            <p class="text-base md:text-lg font-semibold text-white/75 mb-3 font-heading tracking-widest uppercase reveal">
                Building Healthier Communities Through Service
            </p>

            <!-- Body text -->
            <p class="text-base md:text-lg text-white/60 leading-relaxed mb-10 max-w-xl reveal">
                Mobilizing volunteers, responders, and community leaders to strengthen health, preparedness, and community resilience.            </p>

            <!-- CTA(call to action) -->
            <div class="flex flex-col sm:flex-row gap-4 mt-2 reveal">
                <a
                    href="{{ url('/what-we-do') }}"
                    id="hero-cta-programs"
                    class="btn-ghost-fill"
                >
                    <span>Explore Programs</span>
                    <svg class="w-3.5 h-3.5 relative z-10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>

        </div>
    </div>


</section>



<!-- SECTION 2 — MANTRA & IMPACT STORIES -->
<section id="stories" class="bg-white" aria-labelledby="stories-heading">

    

    <!-- Story Cards -->
    <div class="w-full flex flex-col gap-16 md:gap-20 pt-14 md:pt-16 pb-12 overflow-x-hidden">

        <!-- Story 1 -->
        <div class="relative flex flex-col md:flex-row items-stretch">
            <div class="w-full md:w-[68%] relative h-[380px] md:h-[520px] overflow-hidden flex-shrink-0">
                <img
                    src="{{ asset('images/storycard.png') }}"
                    class="absolute inset-0 w-full h-full object-cover"
                    alt="Youth responders in action"
                    loading="lazy"
                />
            <div class="absolute inset-0 bg-gradient-to-r from-transparent to-[#1a1a1a]/30">
                
            </div>
            </div>
            <!-- Text panel -->
            <div class="reveal reveal-right w-full md:w-[44%] md:-ml-16 z-10 self-center md:my-12">
                <div class="story-card bg-[#1a1a1a] p-10 md:p-12 lg:p-16 flex flex-col justify-center text-white h-full">
                    <div class="story-rule"></div>
                    <span class="text-[10px] font-bold tracking-[0.2em] uppercase text-[#F97316]/70 mb-3">Field Operations</span>
                    <h3 class="text-3xl md:text-4xl font-heading font-bold uppercase mb-5 leading-tight tracking-tight">
                        Racing Against<br>
                The Clock
            </h3>
            <p class="text-white/65 text-sm md:text-base leading-relaxed mb-8 font-light">
                Using cargo bikes to be on the move, youth volunteers in Pangasinan make it their mission to help provide basic medical services to residents of Barangay Umanday.            </p>
            <a href="{{ url('https://www.rappler.com/moveph/262436-pangasinan-youth-volunteers-bike-answer-communtity-health-needs/') }}" class="text-sm font-semibold text-[#2FA7FF] hover:text-white transition-colors flex items-center gap-2 group">
                        Read the Story in Rappler
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Story 2 -->
        <div class="relative flex flex-col md:flex-row-reverse items-stretch" x-data="{ playing: false }">
            <div class="w-full md:w-[72%] relative h-[440px] md:h-[640px] overflow-hidden flex-shrink-0 group cursor-pointer"
                 @click="playing ? $refs.video.pause() : $refs.video.play(); playing = !playing">
                <video
                    x-ref="video"
                    loop
                    muted
                    playsinline
                    class="absolute inset-0 w-full h-full object-cover"
                >
                    <source src="{{ asset('videos/story_2_video.mp4') }}" type="video/mp4">
                </video>
                <div class="absolute inset-0 bg-gradient-to-l from-transparent to-[#1a1a1a]/30 pointer-events-none"></div>

                <!-- Play/Pause Button -->
                <button
                    @click.stop="playing ? $refs.video.pause() : $refs.video.play(); playing = !playing"
                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-20 w-14 h-14 md:w-16 md:h-16 bg-black/40 hover:bg-black/60 border border-white/20 backdrop-blur-md text-white rounded-full flex items-center justify-center shadow-lg transition-all duration-300 opacity-40 group-hover:opacity-100"
                    aria-label="Play/Pause Video"
                >
                    <svg x-show="playing" style="display: none;" class="w-6 h-6 md:w-7 md:h-7" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M6.75 5.25a.75.75 0 0 1 .75-.75H9a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75H7.5a.75.75 0 0 1-.75-.75V5.25Zm7.5 0A.75.75 0 0 1 15 4.5h1.5a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75H15a.75.75 0 0 1-.75-.75V5.25Z" clip-rule="evenodd" /></svg>
                    <svg x-show="!playing" class="w-6 h-6 md:w-7 md:h-7 ml-0.5 md:ml-1" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd" /></svg>
                </button>
            </div>
            <!-- Text panel -->
            <div class="reveal reveal-left w-full md:w-[56%] z-10 self-center md:my-12">
                <div class="story-card bg-[#1a1a1a] p-10 md:p-12 lg:p-16 flex flex-col justify-center text-white h-full min-h-[400px] md:min-h-[500px] md:-mr-16 transition-[width] duration-500 ease-in-out"
                     :class="playing ? 'md:w-[calc(100%_+_4rem)]' : 'md:w-[calc(100%_+_12rem)]'">
                    <div class="story-rule"></div>
                    <h3 class="text-3xl md:text-4xl font-heading font-bold uppercase mb-5 leading-tight tracking-tight">
                        Empowering The<br>
                        Next Generation
                    </h3>
                    <p class="text-white/65 text-sm md:text-base leading-relaxed mb-8 font-light">
                        Bicycles do more than transport; they unlock potential. We see young riders turning into community leaders, 
                        taking responsibility for the health and safety of their neighbors.
                    </p>
                
            </div>
        </div>
    </div>

    <!-- Story 3 -->
    <div class="relative flex flex-col md:flex-row items-stretch" x-data="{ playing: false }">
        <div class="w-full md:w-[72%] relative h-[440px] md:h-[640px] overflow-hidden flex-shrink-0 group cursor-pointer"
             @click="playing ? $refs.video.pause() : $refs.video.play(); playing = !playing">
            <video
                x-ref="video"
                loop
                muted
                playsinline
                class="absolute inset-0 w-full h-full object-cover"
            >
                <source src="{{ asset('videos/story_2_video.mp4') }}" type="video/mp4">
            </video>
            <div class="absolute inset-0 bg-gradient-to-l from-transparent to-[#1a1a1a]/30 pointer-events-none"></div>

            <!-- Play/Pause Button -->
            <button
                @click.stop="playing ? $refs.video.pause() : $refs.video.play(); playing = !playing"
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-20 w-14 h-14 md:w-16 md:h-16 bg-black/40 hover:bg-black/60 border border-white/20 backdrop-blur-md text-white rounded-full flex items-center justify-center shadow-lg transition-all duration-300 opacity-40 group-hover:opacity-100"
                aria-label="Play/Pause Video"
            >
                <svg x-show="playing" style="display: none;" class="w-6 h-6 md:w-7 md:h-7" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M6.75 5.25a.75.75 0 0 1 .75-.75H9a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75H7.5a.75.75 0 0 1-.75-.75V5.25Zm7.5 0A.75.75 0 0 1 15 4.5h1.5a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75H15a.75.75 0 0 1-.75-.75V5.25Z" clip-rule="evenodd" /></svg>
                <svg x-show="!playing" class="w-6 h-6 md:w-7 md:h-7 ml-0.5 md:ml-1" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd" /></svg>
            </button>
        </div>
        <!-- Text panel -->
        <div class="reveal reveal-right w-full md:w-[56%] md:-ml-16 z-10 self-center md:my-12">
            <div class="story-card bg-[#1a1a1a] p-10 md:p-12 lg:p-16 flex flex-col justify-center text-white h-full min-h-[400px] md:min-h-[500px] md:-mr-16 transition-[width,translate] duration-500 ease-in-out"
                 :class="playing ? 'md:w-[calc(100%_+_4rem)] md:-translate-x-16' : 'md:w-[calc(100%_+_12rem)] md:-translate-x-32'">
                <div class="story-rule"></div>
                <h3 class="text-3xl md:text-4xl font-heading font-bold uppercase mb-5 leading-tight tracking-tight">
                    Empowering The<br>
                    Next Generation
                </h3>
                <p class="max-w-[30rem] text-white/65 text-sm md:text-base leading-relaxed mb-8 font-light">
                    Bicycles do more than transport; they unlock potential. We see young riders turning into community leaders, taking responsibility for the health and safety of their neighbors.
                </p>
                
            </div>
        </div>
    </div>

    </div>
</section>


     <!-- SECTION 3 — PROGRAMS SLIDER -->
<section
    id="programs"
    class="relative bg-[#111827]"
    aria-labelledby="programs-heading"
    x-data="programSlider()"
>
    <!-- Header Bar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-4 flex justify-center">
        <div class="text-center">
            <!-- <span class="section-eyebrow mb-2 inline-flex">Our Programs</span> -->
            <h2 id="programs-heading" class="text-4xl md:text-5xl font-heading font-bold text-white uppercase text-center">
                LEARN MORE ABOUT OUR PROGRAMS
            </h2>
        </div>

    </div>

    <!-- Slider -->
    <div
        class="relative w-full overflow-hidden bg-[#111827] min-h-[580px] md:min-h-[640px] touch-pan-y"
        @touchstart.passive="touchStart($event)"
        @touchend.passive="touchEnd($event)"
    >
        <!-- Background slides -->
        <template x-for="(prog, index) in programs" :key="index">
            <div
                x-show="active === index"
                x-transition:enter="transition-opacity duration-1000 z-10"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-1000 z-0"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 w-full h-full"
            >
                <img :src="prog.img" :alt="prog.title"
                     class="absolute inset-0 w-full h-full object-cover transform transition-transform duration-[10s] ease-out"
                     :class="active === index ? 'scale-105' : 'scale-100'">
                <!-- Rich overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-[#111827] via-[#111827]/50 to-transparent opacity-100"></div>
                <div class="absolute inset-0" :style="'background: radial-gradient(ellipse at 80% 20%, ' + prog.accent + '18 0%, transparent 60%)'"></div>
            </div>
        </template>

        <!-- Content Area -->
        <div class="absolute inset-0 z-20 flex items-center md:items-end pb-6 md:pb-12 px-4 sm:px-8 lg:px-16">
            <div class="w-full max-w-5xl relative h-auto">
                <template x-for="(prog, index) in programs" :key="'c' + index">
                    <div
                        x-show="active === index"
                        x-transition:enter="transition ease-out duration-700 delay-200 z-10 relative"
                        x-transition:enter-start="opacity-0 translate-y-8"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-300 absolute inset-0 z-0"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="w-full max-w-sm py-10 px-6 pb-6 md:px-8 md:py-12 md:pb-8 flex flex-col justify-end min-h-[400px]"
                        @mouseenter="pause()"
                        @mouseleave="resume()"
                    >
                        <h3 class="text-3xl md:text-4xl font-heading font-extrabold text-white mb-6 uppercase tracking-wide drop-shadow-md leading-tight" x-text="prog.title"></h3>
                        <p class="text-white/80 text-sm md:text-base font-light mb-10 leading-relaxed drop-shadow-sm max-w-sm" x-text="prog.desc"></p>
                        <a
                            :href="prog.href"
                            class="group inline-flex items-center justify-center gap-3 px-6 py-3 text-xs md:text-sm font-bold text-white border border-white/30 transition-all duration-300 tracking-[0.15em] uppercase rounded-none shadow-lg"
                        >
                            Learn More
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </template>
            </div>
        </div>

        <!-- Navigation controls -->
        <div class="absolute left-1/2 bottom-8 z-30 flex -translate-x-1/2 items-center gap-3">
            <div class="flex gap-2">
                <template x-for="(prog, index) in programs" :key="'d' + index">
                    <button
                        @click="goTo(index)"
                        class="h-2 rounded-full transition-all duration-300"
                        :class="active === index ? 'w-7 bg-[#F97316]' : 'w-2 bg-white/35 hover:bg-white/60'"
                        :aria-label="'Go to slide ' + (index + 1)"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    </div>
</section>

<!-- SECTION 4 — QUICK ACCESS CARDS -->
 
<section
    id="quick-access"
    class="py-20 md:py-24 bg-[#F8FAFC]"
    aria-labelledby="quick-access-heading"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14 reveal">
            <!-- <span class="section-eyebrow mb-4 inline-flex">Navigation</span> -->
            <h2 id="quick-access-heading" class="text-section-title-md font-heading font-bold text-[#b55918ff] mt-3">
                Find What You Need
            </h2>
            <p class="text-[#64748B] mt-3 max-w-md mx-auto text-sm leading-relaxed">
                Quickly navigate to our key services and programs designed to serve the community.
            </p>
        </div>

        @php
        $quickLinks = [
            [
                'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>',
                'bg'     => 'bg-[#132D6B]',
                'accent' => '#132D6B',
                'title'  => 'Programs',
                'desc'   => 'Explore health, outreach, and disaster readiness initiatives.',
                'href'   => '/what-we-do',
                'tag'    => 'Community Service',
            ],
            [
                'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>',
                'bg'     => 'bg-[#b55918ff]',
                'accent' => '#b55918ff',
                'title'  => 'Volunteer',
                'desc'   => 'Join our growing team of youth community responders.',
                'href'   => '/volunteer',
                'tag'    => 'Get Involved',
            ],
            [
                'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/>',
                'bg'     => 'bg-[#1a6da9ff]',
                'accent' => '#1a6da9ff',
                'title'  => 'News & Updates',
                'desc'   => 'Stay informed on our latest field operations and news.',
                'href'   => '/news',
                'tag'    => 'Latest Updates',
            ],
            [
                'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>',
                'bg'     => 'bg-[#0f8a3cff]',
                'accent' => '#0f8a3cff',
                'title'  => 'Contact Us',
                'desc'   => 'Reach our team for partnerships, media, or inquiries.',
                'href'   => '/contact',
                'tag'    => 'Get in Touch',
            ],
        ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ($quickLinks as $i => $link)
            <a
                href="{{ url($link['href']) }}"
                id="quick-link-{{ $i }}"
                class="quick-card flex flex-col gap-4 p-6 reveal reveal-delay-{{ $i + 1 }}"
                aria-label="{{ $link['title'] }}"
            >
                <!-- Icon -->
                <div class="w-12 h-12 rounded-sm {{ $link['bg'] }} flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        {!! $link['icon'] !!}
                    </svg>
                </div>

                <!-- Tag -->
                <span class="text-[9px] font-bold tracking-[0.18em] uppercase" style="color: {{ $link['accent'] }}">{{ $link['tag'] }}</span>

                <div>
                    <h3 class="font-heading font-semibold text-[#111827] text-lg mb-1.5">
                        {{ $link['title'] }}
                    </h3>
                    <p class="text-sm text-[#64748B] leading-relaxed">{{ $link['desc'] }}</p>
                </div>

                <div class="flex items-center gap-1.5 text-xs font-bold mt-auto"
                     style="color: {{ $link['accent'] }}">
                    Explore
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </div>
            </a>
            @endforeach
        </div>

    </div>
</section>


<!-- SECTION 5 impact stat -->

<section
    id="impact"
    class="border-y border-slate-200 bg-[#F8FAFC] py-4 md:py-5"
    aria-label="Impact statistics"
>
    <div class="mx-auto grid max-w-7xl grid-cols-1 divide-y divide-slate-200 px-4 sm:grid-cols-2 sm:divide-y-0 sm:divide-x sm:px-6 lg:grid-cols-4">
        @foreach ($impactStats as $stat)
            <div class="flex flex-col items-center justify-center px-4 py-7 text-center md:py-8">
                <span class="text-stat font-heading font-semibold tracking-tight text-[#132D6B]" data-count-up="{{ $stat->value }}">{{ $stat->value }}</span>
                <span class="mt-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">{{ $stat->label }}</span>
            </div>
        @endforeach
    </div>
</section>
 
<!-- 
     SECTION 6 — featured impact -->
<section
    id="featured-impact"
    class="bg-white pt-20 pb-10 md:pt-28 md:pb-12 overflow-hidden"
    aria-labelledby="impact-story-heading"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">

            <!-- Left Image chnage -->
            <div class="relative reveal">
                <div class="relative rounded-sm overflow-hidden shadow-lg aspect-[4/3]">
                    <img
                        src="{{ asset('images/impact-story.jpg') }}"
                        alt="Community gathering around bicycles at sunset"
                        class="w-full h-full object-cover transition-transform duration-700 hover:scale-[1.04]"
                        loading="lazy"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0D1B2A]/50 to-transparent"></div>
                </div>

                <!-- Floating stat badge -->
                @if ($impactBadge)
                    <div class="absolute -bottom-6 -right-4 sm:-right-6 bg-[#132D6B] text-white rounded-sm px-6 py-5 shadow-xl hidden sm:block">
                        <div class="font-heading font-bold text-3xl tracking-tight" data-count-up="{{ $impactBadge->value }}">{{ $impactBadge->value }}</div>
                        <div class="text-[10px] text-white/55 uppercase tracking-[0.18em] mt-0.5">{{ $impactBadge->label }}</div>
                    </div>
                @endif

            </div>

            <!-- Right: Story -->
            <div class="flex flex-col gap-7 reveal">
                <div>
                    <!-- <span class="section-eyebrow mb-4 inline-flex">Impact Story</span> -->
                    <h2
                        id="impact-story-heading"
                        class="text-impact-title font-heading font-bold text-[#111827] leading-tight mb-4 mt-3"
                    >
                        Every Ride Creates<br/>
                        <span class="text-[#132D6B]">Community Impact</span>
                    </h2>
                    <p class="text-[#64748B] leading-relaxed text-base">
                        Our volunteer cyclists respond to community needs across Pangasinan. Each ride represents a connection — a health check delivered, a family reached, a community made more resilient.
                    </p>
                </div>

                <!-- Impact highlights -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($impactHighlights as $highlight)
                    <div class="impact-stat-card">
                        <div class="w-1 self-stretch rounded-full shrink-0 bg-[#111827]"></div>
                        <div>
                            <div class="font-heading font-bold text-xl text-[#132D6B]" data-count-up="{{ $highlight->value }}">{{ $highlight->value }}</div>
                            <div class="text-xs text-[#64748B] leading-snug mt-0.5">{{ $highlight->label }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 mt-1">
                    <a
                        href="{{ url('/about') }}"
                        class="group inline-flex items-center justify-center gap-2 px-7 py-3.5 text-sm font-semibold bg-[#132D6B] text-white hover:bg-[#132D6B] transition-all duration-300 shadow-sm hover:shadow-md rounded-none"
                    >
                        Our Full Story
                        <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="{{ url('/donate') }}" class="btn-wbr btn-wbr-navy">
                        <span>Support Our Rides</span>
                        <svg class="w-4 h-4 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- SECTION 7 — LATEST NEWS -->
<section
    id="latest-news"
    class="bg-[#F8FAFC] pt-10 pb-16 md:pt-12 md:pb-24"
    aria-labelledby="news-heading"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-9 flex flex-col gap-6 border-b border-slate-200 pb-7 reveal sm:flex-row sm:items-end sm:justify-between md:mb-12">
            <div class="max-w-2xl">

                <h2 id="news-heading" class="font-heading text-4xl font-bold uppercase leading-tight text-[#111827] md:text-5xl">
                    News &amp; Updates
                </h2>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#64748B] md:text-base">
                    The latest stories, activities, and updates from the OneGoBike community.
                </p>
            </div>
            <a href="{{ url('/news') }}" class="group inline-flex min-h-11 shrink-0 items-center justify-center gap-2 border border-[#132D6B]/20 bg-white px-5 py-3 text-xs font-bold uppercase tracking-[0.12em] text-[#132D6B] transition-colors hover:border-[#132D6B] hover:bg-[#132D6B] hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2FA7FF] focus-visible:ring-offset-2">
                View All News
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7">
            @if(isset($latestNews) && $latestNews->count() > 0)
                @foreach($latestNews as $i => $newsItem)
                <div class="news-card reveal reveal-delay-{{ $i + 1 }}">
                    <div class="news-card-image-wrap">
                        <img src="{{ $newsItem->image_path ? asset($newsItem->image_path) : asset('images/gobike-logo.png') }}" alt="{{ $newsItem->title }}" class="news-card-image" loading="lazy">
                    </div>
                    <div class="news-card-content">
                        <div class="news-card-date">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25m10.5-2.25v2.25M3.75 8.25h16.5M5.25 4.5h13.5A1.5 1.5 0 0 1 20.25 6v14.25H3.75V6a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
                            <time datetime="{{ $newsItem->published_at->toDateString() }}">{{ $newsItem->published_at->format('F d, Y') }}</time>
                        </div>
                        <h3 class="news-card-title">{{ $newsItem->title }}</h3>
                        <p class="news-card-excerpt">{{ $newsItem->summary }}</p>
                        
                        <a href="{{ url('/news') }}" class="news-card-link mt-auto group" aria-label="Read more news: {{ $newsItem->title }}">
                            Read story
                            <svg class="h-5 w-5 shrink-0 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
                @endforeach
            @else
                <p class="col-span-full border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-[#64748B]">No news available at the moment.</p>
            @endif
        </div>
    </div>
</section>



 <!-- SECTION 8 — CALL TO ACTN (CTA)     -->
<section
    id="cta"
    class="relative py-5 md:py-30 overflow-hidden"
    aria-labelledby="cta-heading"
>
    <!-- Background Map -->
    <div class="absolute inset-0 z-0 bg-[#F8FAFC]">
        <img src="{{ asset('images/pangasinan-map.png') }}" alt="Map of Pangasinan" class="w-full h-full object-cover opacity-[0.3] mix-blend-multiply scale-105">
        <!-- Light overlay to fade and ensure text remains readable -->
        <!-- <div class="absolute inset-0 bg-gradient-to-t from-[#F8FAFC]/50 via-transparent to-[#F8FAFC]/90"></div> -->
    </div>
 
    <!--  Grid pattern overlay -->
    <!-- <div class="cta-grid-pattern absolute inset-0 opacity-30" aria-hidden="true" style="filter: invert(1);"></div>  -->
 
 
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
 
        <h2
            id="cta-heading"
            class="text-cta-title font-heading font-bold text-[#111827] mb-6 leading-tight"
        >
            Ready To Make<br/>
            An <span class="text-[#2FA7FF]">Impact?</span>
        </h2>
 
        <p class="text-base md:text-lg text-[#64748B] font-medium leading-relaxed mb-10 max-w-2xl mx-auto">
            Every act of service creates lasting impact. Join volunteers, partners, and community leaders working together to build healthier, safer, and more resilient communities.        </p>
 
        <div class="flex flex-col sm:flex-row gap-4 justify-center mt-6">
            <a
                href="{{ url('/contact') }}"
                id="cta-volunteer"
                class="group inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#132D6B] text-white text-sm font-bold tracking-[0.1em] uppercase transition-all duration-300 shadow-lg shadow-[#132D6B]/30 hover:bg-[#2FA7FF] rounded-none"
            >
                <span>Volunteer With Us</span>
                <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
            <!-- <a href="{{ url('/donate') }}" id="cta-donate" class="btn-wbr">
                <span>Donate Now</span>
                <svg class="w-4 h-4 arrow" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a> -->
        </div>
 
    </div>
</section>


<x-slot:scripts>
    <script src="{{ asset('js/home.js') }}"></script>
</x-slot:scripts>

</x-layout>
