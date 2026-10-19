<x-layout 
    title="{{ $document->title ?? 'Terms & Conditions' }} | Go Bike Project"
    description="Terms and Conditions of use and community participation for Go Bike Project."
>
    <!-- HERO SECTION (Matching About page height and styling) -->
    <section class="relative pt-32 pb-24 md:pt-48 md:pb-36 overflow-hidden bg-[#0D1B2A]">
        <!-- Decorative blobs -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-[#2FA7FF]/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-[#F97316]/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-heading font-bold text-white mb-4 uppercase tracking-tight leading-none drop-shadow-lg">
                {{ $document->title ?? 'Terms & Conditions' }}
            </h1>
            <p class="text-[#F97316] font-semibold tracking-wide text-sm uppercase">
                Last updated: {{ $document->last_updated ?? date('F d, Y') }}
            </p>
        </div>
    </section>

    <!-- CONTENT SECTION -->
    <section class="py-16 md:py-24 bg-[#0D1B2A] text-white border-t border-white/5 relative overflow-hidden">
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="space-y-10 text-white/80 leading-relaxed text-base md:text-lg">
                @forelse ($document->sections ?? [] as $section)
                    <div class="reveal">
                        @if (!empty($section['title']))
                            <h2 class="text-2xl md:text-3xl font-heading font-bold text-white mb-3">
                                {{ $section['title'] }}
                            </h2>
                        @endif
                        @if (!empty($section['content']))
                            <div class="space-y-4 text-white/75 leading-relaxed">
                                {!! $document->renderSectionContent($section['content']) !!}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-12 text-white/50">
                        <p>No terms have been published yet. Please check back later.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-layout>
