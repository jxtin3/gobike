<x-layout title="News & Updates | Go Bike" description="Stay informed on our latest field operations and news.">
    <!-- Hero -->
    <section class="relative min-h-[460px] md:min-h-[500px] flex flex-col justify-center pt-28 pb-16 md:pt-32 md:pb-20 overflow-hidden bg-[#0D1B2A]">
        <div class="absolute inset-0 z-0">
            <img
                src="{{ asset('images/jointhemovement/news.png') }}"
                alt="OneGoBike News"
                class="w-full h-full object-cover object-[center_35%] opacity-45"
            />
            <div class="absolute inset-0 bg-gradient-to-b from-[#0D1B2A]/80 via-[#0D1B2A]/40 to-[#0D1B2A]/60"></div>
        </div>

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal w-full">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white uppercase mb-5 tracking-tight leading-tight drop-shadow-lg">News &amp; Updates</h1>
            <p class="text-white/65 max-w-2xl mx-auto text-base md:text-lg leading-relaxed">Stay informed on our latest field operations, announcements, and community impact stories across Pangasinan.</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="py-16 md:py-24 bg-[#F8FAFC]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @if($news->count() > 0)
                    @foreach($news as $i => $newsItem)
                    <div class="bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col h-full rounded-sm overflow-hidden reveal reveal-delay-{{ ($i % 3) + 1 }}">
                        {{-- Articles open in a new tab so the News list stays open --}}
                        <a href="{{ route('news.show', $newsItem->slug) }}" target="_blank" rel="noopener" class="relative block w-full aspect-video overflow-hidden bg-slate-100" tabindex="-1" aria-hidden="true">
                            <img src="{{ $newsItem->image_path ? \App\Support\Media::url($newsItem->image_path) : asset('images/gobike-logo.png') }}" alt="" class="w-full h-full object-cover" loading="lazy">
                        </a>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="text-xs font-semibold text-slate-500 mb-2">{{ $newsItem->published_at->format('F d, Y') }}</div>
                            <h3 class="text-xl font-heading font-bold text-slate-900 leading-tight mb-3 line-clamp-2">
                                <a href="{{ route('news.show', $newsItem->slug) }}" target="_blank" rel="noopener" class="text-inherit no-underline hover:text-[#2563EB]">{{ $newsItem->title }}<span class="sr-only"> (opens in a new tab)</span></a>
                            </h3>
                            <p class="text-sm text-slate-600 mb-6 flex-grow line-clamp-3">{{ $newsItem->summary }}</p>
                            <a href="{{ route('news.show', $newsItem->slug) }}" target="_blank" rel="noopener" aria-label="Read more: {{ $newsItem->title }} (opens in a new tab)" class="flex justify-center text-sm font-bold text-white bg-[#2563EB] hover:bg-[#2FA7FF] no-underline px-5 py-2.5 rounded-full transition-all duration-300 uppercase tracking-wide mt-auto shadow-sm hover:shadow-md">
                                Read More
                            </a>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="col-span-full text-center py-12 reveal">
                        <p class="text-slate-500 text-lg">No news available at the moment. Please check back later.</p>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($news->hasPages())
            <div class="mt-12 flex justify-center reveal">
                {{ $news->links() }}
            </div>
            @endif
        </div>
    </div>

<!-- volunteer -->
        @include('partials.volunteer-card')


</x-layout>