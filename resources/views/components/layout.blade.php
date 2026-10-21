<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#0D1B2A" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

     <!-- SEO  seacrh engine opti -->
    <title>{{ $title ?? 'Go Bike — Youth-Led Community Health Responders' }}</title>
    <meta name="description" content="{{ $description ?? 'Go Bike mobilizes youth volunteers, cyclists, and responders to improve health, resilience, disaster preparedness, and community engagement throughout Pangasinan.' }}" />
    <meta name="keywords" content="OneGoBike,Go Bike Project, Pangasinan, youth volunteers, community responders, Go Bikers, disaster preparedness, health outreach" />

     <!-- Open Graph for sharing in socmed -->
    <meta property="og:type"        content="website" />
    <meta property="og:url"         content="{{ url()->current() }}" />
    <meta property="og:title"       content="{{ $title ?? 'Go Bike — Youth-Led Community Health Responders' }}" />
    <meta property="og:description" content="{{ $description ?? 'Promotes community engagement and educational initiatives, empowering communities to build a healthier, greener, and sustainable future' }}" />
    <meta property="og:image"       content="{{ $image ?? asset('images/gobike-logo.png') }}" />

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/gobike-logo.png') }}" />

    <!-- Self-hosted fonts (Inter + Oswald) -->
    @fonts

    <!-- Vite assets (Tailwind CSS + JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Additional head slot -->
    {{ $head ?? '' }}
</head>

<body class="bg-[#F8FAFC] text-[#111827] antialiased" x-data="{ mobileOpen: false, scrolled: false }" @keydown.escape.window="mobileOpen = false">

    <!-- Header -->
    <header
        id="site-header"
        class="site-header fixed top-0 inset-x-0 z-50 border-b"
        x-init="
            window.addEventListener('scroll', () => {
                scrolled = window.scrollY > 60;
            }, { passive: true });
        "
        :class="scrolled
            ? 'is-scrolled'
            : ''"
    >
        <div class="w-full px-6 sm:px-10 md:px-12 lg:px-16 xl:px-20 2xl:px-24">
            <div class="flex items-center justify-between h-16 md:h-20 lg:h-[84px]">

                <!-- Logo -->
                <a
                    href="{{ url('/') }}"
                    class="flex items-center gap-3 group shrink-0"
                    aria-label="OneGoBike Home"
                >
                    <div class="flex flex-col leading-none">
                        <span class="font-heading text-xl md:text-2xl font-bold tracking-wider">
                            <span class="text-[#F97316]">One</span><span class="text-[#2FA7FF]">GoBike</span>
                        </span>
                        <span class="text-[9px] md:text-[10px] font-semibold tracking-[0.2em] text-white/50 uppercase mt-0.5">Community Responders</span>
                    </div>
                </a>

                <!-- Desktop Navigation  -->
                <nav class="hidden md:flex items-center gap-7 lg:gap-8" aria-label="Primary navigation">
                    <!-- home -->
                    <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false">
                        <button type="button" class="nav-underline text-sm md:text-[15px] font-medium tracking-wide transition-colors duration-200 text-white/90 hover:text-white flex items-center gap-1 pb-1">
                            Who We Are
                            <svg class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute top-full left-0 mt-1 w-52 bg-[#0D1B2A]/95 backdrop-blur-lg border border-white/10 rounded-sm shadow-xl py-2 z-50">
                            <a href="{{ url('/about') }}" class="block px-4 py-2.5 text-sm text-white/80 hover:text-white hover:bg-white/10 transition-colors">About Us</a>
                            <a href="{{ url('/org-structure') }}" class="block px-4 py-2.5 text-sm text-white/80 hover:text-white hover:bg-white/10 transition-colors">Org Structure &amp; History</a>
                        </div>
                    </div>

                    <!-- programs -->
                    <x-nav-link href="{{ url('/what-we-do') }}">What We Do</x-nav-link> 

                    <!-- community -->
                    <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false">
                        <button type="button" class="nav-underline text-sm md:text-[15px] font-medium tracking-wide transition-colors duration-200 text-white/90 hover:text-white flex items-center gap-1 pb-1">
                            Join The Movement
                            <svg class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute top-full left-0 mt-1 w-52 bg-[#0D1B2A]/95 backdrop-blur-lg border border-white/10 rounded-sm shadow-xl py-2 z-50">
                            <a href="{{ url('/news') }}" class="block px-4 py-2.5 text-sm text-white/80 hover:text-white hover:bg-white/10 transition-colors">News &amp; Updates</a>
                            <a href="{{ url('/gallery') }}" class="block px-4 py-2.5 text-sm text-white/80 hover:text-white hover:bg-white/10 transition-colors">Gallery Showcase</a>
                        </div>
                    </div>
                    
                    <!-- contact -->
                    <x-nav-link href="{{ url('/contact') }}">Reach Us</x-nav-link>
                </nav>

                <!-- Right Side call to action (cta)  -->
                <div class="hidden md:flex items-center gap-3 mr-4 md:mr-8 lg:mr-14 xl:mr-20 2xl:mr-24">
                   
                    <a
                        href="{{ url('/donate') }}"
                        class="inline-flex items-center justify-center px-6 py-2.5 md:px-7 md:py-2.5 text-xs font-bold tracking-[0.15em] uppercase bg-[#F97316] hover:bg-[#ea580c] transition-colors duration-200 text-white shadow-md rounded-none"
                    >
                        Donate
                    </a>
                </div>

                <!--  Hamburger (mobile)  -->
                <button
                    id="mobile-menu-toggle"
                    type="button"
                    class="md:hidden p-2 rounded-none text-white hover:bg-white/10 transition-colors duration-200"
                    :aria-expanded="mobileOpen"
                    aria-controls="mobile-menu"
                    aria-label="Toggle mobile menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <span class="sr-only">Toggle menu</span>
                    <!-- Hamburger icon morphs to X  -->
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

            </div>
        </div>

        <!-- Mobile Menu  -->
        <div
            id="mobile-menu"
            x-show="mobileOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden bg-[#0D1B2A]/97 backdrop-blur-lg border-t border-white/10"
            @click.outside="mobileOpen = false"
        >
            <nav class="w-full px-6 py-4 flex flex-col gap-1" aria-label="Mobile navigation">
                <div x-data="{ open: false }" class="flex flex-col">
                    <button type="button" @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-base font-medium rounded-none transition-colors duration-200 text-white/90 hover:bg-white/10 hover:text-white">
                        <span>Who We Are</span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-collapse class="flex flex-col gap-1 pl-6 pr-4 py-1">
                        <a href="{{ url('/about') }}" @click="mobileOpen = false" class="block px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/10 rounded-none transition-colors">About Us</a>
                        <a href="{{ url('/org-structure') }}" @click="mobileOpen = false" class="block px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/10 rounded-none transition-colors">Org Structure &amp; History</a>
                    </div>
                </div>
                <x-nav-link :mobile="true" href="{{ url('/what-we-do') }}"   @click="mobileOpen = false">What We Do</x-nav-link>
                <div x-data="{ open: false }" class="flex flex-col">
                    <button type="button" @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-base font-medium rounded-none transition-colors duration-200 text-white/90 hover:bg-white/10 hover:text-white">
                        <span>Join The Movement</span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-collapse class="flex flex-col gap-1 pl-6 pr-4 py-1">
                        <a href="{{ url('/news') }}" @click="mobileOpen = false" class="block px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/10 rounded-none transition-colors">News &amp; Updates</a>
                        <a href="{{ url('/gallery') }}" @click="mobileOpen = false" class="block px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/10 rounded-none transition-colors">Gallery Showcase</a>
                    </div>
                </div>
                <x-nav-link :mobile="true" href="{{ url('/contact') }}"    @click="mobileOpen = false">Contact</x-nav-link>

                <div class="mt-3 pt-3 border-t border-white/10">
                    <a
                        href="{{ url('/donate') }}"
                        class="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-none text-xs font-bold tracking-[0.15em] uppercase bg-[#F97316] text-white shadow-md"
                        @click="mobileOpen = false"
                    >
                        <!-- <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg> -->
                        Donate Now
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        {{ $slot }}
    </main>

    
    <!-- Shared helpers -->
    <script defer src="{{ asset('js/counter.js') }}?v={{ filemtime(public_path('js/counter.js')) }}"></script>

    <!-- Alpine.js -->
    <script defer src="{{ asset('js/alpine-intersect.min.js') }}"></script>
    <script defer src="{{ asset('js/alpine-collapse.min.js') }}"></script>
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>

    <!-- Footer -->
    <footer class="bg-[#0D1B2A] text-white relative overflow-hidden" aria-label="Site footer">

        <!-- Decorative background accent -->
        <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-[#132D6B]/15 blur-3xl pointer-events-none"></div>

        <!-- Main footer grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-10 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 lg:gap-12">

                <!-- Column 1 - Brand + Social (wider) -->
                <div class="md:col-span-2 flex flex-col gap-5">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-3" aria-label="OneGoBike Home">
                        <div>
                            <span class="font-heading text-2xl font-bold tracking-wider block">
                                <span class="text-[#F97316]">One</span><span class="text-[#2FA7FF]">GoBike</span>
                            </span>
                            <p class="text-[10px] tracking-[0.18em] text-white/40 uppercase">Community Responders</p>
                        </div>
                    </a>
                    <p class="text-sm text-white/55 leading-relaxed max-w-sm">
                        Empowering communities through health initiatives, disaster preparedness, volunteer engagement, and community-driven services that create lasting impact.                    </p>
                </div>

                <!-- Column 2 - Quick Links -->
                <div>
                    <h3 class="font-heading text-[11px] font-bold tracking-[0.2em] uppercase text-[#F97316] mb-5">Navigate</h3>
                    <ul class="space-y-2" role="list">
                        @foreach ([
                            ['label' => 'About Us',   'href' => '/about'],
                            ['label' => 'What We Do',   'href' => '/what-we-do'],
                            ['label' => 'Community',  'href' => '/community'],
                            ['label' => 'Contact',    'href' => '/contact'],
                        ] as $link)
                        <li>
                            <a href="{{ url($link['href']) }}" class="text-sm text-white/50 hover:text-[#2FA7FF] transition-colors duration-200 flex items-center gap-2 group">
                                <span class="w-1 h-1 rounded-full bg-[#2FA7FF]/30 group-hover:bg-[#2FA7FF] transition-colors duration-200 shrink-0"></span>
                                {{ $link['label'] }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>

                 <!-- Column 3 – Support  -->
                <div>
                    <h3 class="font-heading text-[11px] font-bold tracking-[0.2em] uppercase text-[#F97316] mb-5">Get Involved</h3>
                    <ul class="space-y-2" role="list">
                        @foreach ([
                            ['label' => 'Volunteer',      'href' => '/contact'],
                            ['label' => 'Donate',         'href' => '/donate'],
                            ['label' => 'News & Updates', 'href' => '/news'],
                            ['label' => 'Gallery',        'href' => '/gallery'],
                        ] as $link)
                        <li>
                            <a href="{{ url($link['href']) }}" class="text-sm text-white/50 hover:text-[#F97316] transition-colors duration-200 flex items-center gap-2 group">
                                <span class="w-1 h-1 rounded-full bg-[#F97316]/30 group-hover:bg-[#F97316] transition-colors duration-200 shrink-0"></span>
                                {{ $link['label'] }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </div>

         <!-- Bottom bar -->
        <div class="border-t border-white/[0.07] relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-white/30">
                <p>&copy; {{ date('Y') }} OneGoBike. All rights reserved. &middot; Pangasinan, Philippines</p>
                <div class="flex items-center gap-4">
                    <a href="{{ url('/privacy-policy') }}" class="hover:text-white/60 transition-colors duration-200">Privacy Policy</a>
                    <span class="opacity-30">&middot;</span>
                    <a href="{{ url('/terms') }}" class="hover:text-white/60 transition-colors duration-200">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>

    </footer>

    <!-- GoBot chat widget -->
    @if ($showChatbot ?? true)
        @include('partials.chatbot')
    @endif

    <!-- Back-to-top visibility script + scroll reveal  -->
    <script src="{{ asset('js/layout.js') }}"></script>
    @if ($showChatbot ?? true)
        <script src="{{ asset('js/chatbot.js') }}"></script>
    @endif

    <!-- Extra scripts slot  -->
    {{ $scripts ?? '' }}

</body>
</html>
