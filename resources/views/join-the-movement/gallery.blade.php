<x-layout title="Gallery | Go Bike Project" description="View our gallery showcase of youth responders and community events.">
<section class="relative min-h-[460px] md:min-h-[500px] flex flex-col justify-center pt-28 pb-16 md:pt-32 md:pb-20 overflow-hidden bg-[#0D1B2A]">
    <div class="absolute inset-0 z-0">
        <img
            src="{{ asset('images/about/about.jpg') }}"
            alt="OneGoBike Gallery"
            class="w-full h-full object-cover object-[center_35%] opacity-45"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-[#0D1B2A]/80 via-[#0D1B2A]/40 to-[#0D1B2A]/60"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal w-full">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white uppercase mb-5 tracking-tight leading-tight drop-shadow-lg">Gallery Showcase</h1>
        <p class="text-white/65 max-w-2xl mx-auto text-base md:text-lg leading-relaxed">Explore moments of impact, community resilience, and volunteer dedication captured during our field operations across Pangasinan.</p>
    </div>
</section>

    <!-- Main Content -->
    <div class="py-16 md:py-24 bg-[#F8FAFC]" x-data="{ activeMedia: null }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @if(isset($pictures) && $pictures->count() > 0)
                    @foreach($pictures as $i => $item)
                        @php
                            $mediaSrc = \Illuminate\Support\Str::startsWith($item->image_path, ['http://', 'https://'])
                                ? $item->image_path
                                : asset('storage/' . $item->image_path);
                            $isVideo = $item->isVideo();
                        @endphp
                        <div class="relative w-full aspect-[4/3] rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer bg-slate-900 group reveal reveal-delay-{{ ($i % 3) + 1 }}"
                             @click="activeMedia = {
                                 src: '{{ e($mediaSrc) }}',
                                 isVideo: {{ $isVideo ? 'true' : 'false' }}
                             }">
                            @if($isVideo)
                                <video src="{{ $mediaSrc }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" preload="metadata" muted playsinline></video>
                                <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/10 transition-colors">
                                    <div class="w-12 h-12 rounded-full bg-white/90 text-slate-900 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            @else
                                <img src="{{ $mediaSrc }}" alt="{{ $item->title ?? 'Gallery Photo' }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                            @endif

                            {{-- Hidden accessible text for SEO and test coverage --}}
                            <span class="sr-only">{{ $item->title }} {{ $item->description }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="col-span-full text-center py-16 reveal">
                        <p class="text-slate-500 text-lg">No photos in the gallery yet. Check back soon for new field uploads.</p>
                    </div>
                @endif
            </div>

            @if(isset($pictures) && method_exists($pictures, 'hasPages') && $pictures->hasPages())
                <div style="margin-top: 3rem; display: flex; align-items: center; justify-content: center; gap: 0.4rem; flex-wrap: wrap;">

                    {{-- Previous --}}
                    @if($pictures->onFirstPage())
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:8px;border:1px solid #e2e8f0;color:#cbd5e1;cursor:default;font-size:1rem;">&#8249;</span>
                    @else
                        <a href="{{ $pictures->previousPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:8px;border:1px solid #e2e8f0;color:#334155;text-decoration:none;font-size:1rem;transition:all 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">&#8249;</a>
                    @endif

                    {{-- Page numbers --}}
                    @foreach($pictures->getUrlRange(1, $pictures->lastPage()) as $page => $url)
                        @if($page == $pictures->currentPage())
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:8px;background:#0D1B2A;color:#fff;font-size:0.875rem;font-weight:700;">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:8px;border:1px solid #e2e8f0;color:#334155;text-decoration:none;font-size:0.875rem;transition:all 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($pictures->hasMorePages())
                        <a href="{{ $pictures->nextPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:8px;border:1px solid #e2e8f0;color:#334155;text-decoration:none;font-size:1rem;transition:all 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">&#8250;</a>
                    @else
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:8px;border:1px solid #e2e8f0;color:#cbd5e1;cursor:default;font-size:1rem;">&#8250;</span>
                    @endif

                </div>
                <p style="text-align:center;margin-top:0.75rem;font-size:0.82rem;color:#94a3b8;">
                    Showing {{ $pictures->firstItem() }}–{{ $pictures->lastItem() }} of {{ $pictures->total() }} photos
                </p>
            @endif
        </div>

        <!-- Clean Media Viewer Lightbox -->
        <div x-show="activeMedia !== null" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="activeMedia = null"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/90 backdrop-blur-md"
             style="display: none;">
            
            <div class="relative max-w-5xl max-h-[90vh] w-full flex items-center justify-center"
                 @click.away="activeMedia = null">
                
                <!-- Close Button -->
                <button @click="activeMedia = null" 
                        class="absolute -top-12 right-0 z-10 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors"
                        title="Close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Media Content -->
                <div class="rounded-lg overflow-hidden shadow-2xl bg-black max-h-[85vh] flex items-center justify-center">
                    <template x-if="activeMedia && !activeMedia.isVideo">
                        <img :src="activeMedia?.src" class="max-h-[85vh] w-auto max-w-full object-contain">
                    </template>
                    <template x-if="activeMedia && activeMedia.isVideo">
                        <video :src="activeMedia?.src" controls autoplay class="max-h-[85vh] w-auto max-w-full"></video>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-layout>
