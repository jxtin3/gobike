<x-layout
    title="Padyakaalaman | OneGoBike"
    description="Recognizing the importance of accessible learning support, Padyarescue Incorporated developed Padyakaalaman to help struggling learners through community volunteerism."
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
                    Padyakaalaman
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
                    Education is one of the most powerful tools for building a better future, yet for many learners, the journey through school is not always easy without adequate support and encouragement.
                </div>
            </header>

            {{-- Hero Image --}}
            <figure class="mb-10">
                <div class="overflow-hidden rounded-sm bg-slate-100 shadow-sm border border-slate-100">
                    <img
                        src="{{ asset('images/whatwedo/padyakaalaman.jpg') }}"
                        alt="Padyakaalaman volunteers and learners in Bugallon, Pangasinan"
                        class="w-full h-auto object-cover max-h-[520px]"
                        loading="eager"
                    />
                </div>
                <figcaption class="mt-2.5 text-xs sm:text-sm text-slate-500 leading-normal">
                    <span class="font-semibold text-slate-700 uppercase tracking-wider text-xs mr-1.5">PHOTO:</span>Padyakaalaman — Padyarescue Incorporated volunteers and community partners providing educational support to students in Pangasinan.
                </figcaption>
            </figure>

            {{-- Article Body --}}
            <div class="article-body max-w-3xl mx-auto space-y-6 text-slate-800 text-base sm:text-lg leading-[1.85] font-normal">

                <p class="first-letter:text-5xl first-letter:font-bold first-letter:font-heading first-letter:text-[#0D1B2A] first-letter:mr-2.5 first-letter:float-left first-letter:leading-none">
                    Education is one of the most powerful tools for building a better future. Yet for many learners, the journey through school is not always easy. Some struggle to keep up with lessons, while others face challenges that affect their ability to learn, participate, and perform academically. Without adequate support and encouragement, these difficulties can affect not only their academic progress but also their confidence and motivation to continue learning.
                </p>

                <p>
                    Recognizing the importance of accessible learning support, <strong class="text-slate-900 font-semibold">Padyarescue Incorporated</strong> developed <strong class="text-slate-900 font-semibold">Padyakaalaman</strong>, an initiative that seeks to help struggling learners by mobilizing volunteers and community partners to contribute to educational development.
                </p>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Learning Beyond the Classroom
                </h2>

                <p>
                    Through volunteer-driven learning support, Padyakaalaman aims to provide learners with additional opportunities to strengthen their understanding, develop their skills, and gain confidence in their abilities. The initiative recognizes that learning does not happen only inside the classroom. With the right guidance, encouragement, and support from their communities, learners can be better equipped to overcome challenges and pursue their educational goals.
                </p>

                {{-- Pullquote --}}
                <div class="my-9 py-5 px-6 sm:px-8 border-l-4 border-[#F97316] bg-orange-50/50 rounded-r-sm">
                    <blockquote class="text-lg sm:text-xl font-medium text-slate-800 italic leading-relaxed">
                        &ldquo;At its heart, Padyakaalaman promotes the idea that education is a shared responsibility. While teachers and schools remain central to the learning process, community volunteers and partner organizations can complement their efforts by helping create a more supportive environment for students who need additional assistance.&rdquo;
                    </blockquote>
                    <p class="mt-2 text-xs sm:text-sm font-semibold uppercase tracking-wider text-[#F97316]">
                        — Padyakaalaman Initiative
                    </p>
                </div>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    Youth Empowerment and Volunteerism
                </h2>

                <p>
                    The initiative also reflects Padyarescue Incorporated's commitment to youth empowerment and volunteerism. By encouraging young people to contribute their time and skills to educational activities, Padyakaalaman creates opportunities for volunteers to become active partners in community development while helping fellow learners progress.
                </p>

                {{-- Mid-article image --}}
                <figure class="my-8">
                    <div class="overflow-hidden rounded-sm bg-slate-100 border border-slate-100">
                        <img
                            src="{{ asset('images/homepage/padyakaalaman.png') }}"
                            alt="Padyakaalaman community learning and mentoring session"
                            class="w-full h-auto object-cover max-h-[440px]"
                            loading="lazy"
                        />
                    </div>
                    <figcaption class="mt-2.5 text-xs sm:text-sm text-slate-500 leading-normal">
                        <span class="font-semibold text-slate-700 uppercase tracking-wider text-xs mr-1.5">PHOTO:</span>Padyakaalaman volunteers and students during community learning activities.
                    </figcaption>
                </figure>

                <h2 class="text-2xl sm:text-3xl font-heading font-bold text-[#0D1B2A] pt-4 mt-10 tracking-tight border-t border-slate-100">
                    A Target of Supporting 500 Struggling Learners
                </h2>

                <p>
                    With a target of supporting 500 struggling learners, the initiative aspires to extend learning opportunities to more students who may benefit from additional guidance and encouragement. Achieving this goal requires collaboration among volunteers, schools, families, and community partners to ensure that learning support responds to the actual needs of students.
                </p>

                <p>
                    More than an educational program, Padyakaalaman is an expression of solidarity with learners who need a little more help along the way. It recognizes that every student learns differently, that challenges should not define a learner's potential, and that communities have a role to play in making education more inclusive.
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
                        <a href="{{ url('/what-we-do/programs/padyakaisipan') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium text-slate-700 hover:border-[#2FA7FF] hover:text-[#2FA7FF] transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            Padyakaisipan
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
