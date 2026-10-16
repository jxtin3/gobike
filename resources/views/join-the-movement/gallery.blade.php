<x-layout title="Gallery | Go Bike Project" description="View our gallery showcase of youth responders and community events.">
<section class="relative pt-32 pb-24 md:pt-48 md:pb-36 overflow-hidden bg-[#0D1B2A]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        <h1 class="text-5xl md:text-6xl lg:text-7xl font-heading font-bold text-white uppercase mb-4 tracking-tight leading-none drop-shadow-lg">Gallery Showcase</h1>
        <p class="text-white/65 max-w-2xl mx-auto text-base md:text-lg leading-relaxed">Explore moments of impact, community resilience, and volunteer dedication captured during our field operations across Pangasinan.</p>
    </div>
</section>

    <!-- Main Content -->
    <div class="py-16 md:py-24 bg-[#F8FAFC]" x-data="{ activeModal: null }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @if(isset($pictures) && $pictures->count() > 0)
                    @foreach($pictures as $i => $item)
                        @php
                            $imgSrc = \Illuminate\Support\Str::startsWith($item->image_path, ['http://', 'https://'])
                                ? $item->image_path
                                : asset('storage/' . $item->image_path);
                            $formattedDate = $item->created_at ? $item->created_at->format('M d, Y') : 'Gallery';
                            $truncatedTitle = \Illuminate\Support\Str::limit($item->title, 55);
                            $truncatedDesc = \Illuminate\Support\Str::limit($item->description, 90);
                        @endphp
                        <div class="relative w-full aspect-[4/3] rounded-sm overflow-hidden group shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer reveal reveal-delay-{{ ($i % 3) + 1 }}"
                             @click="activeModal = {
                                 src: '{{ e($imgSrc) }}',
                                 title: '{{ e($item->title) }}',
                                 date: '{{ e($formattedDate) }}',
                                 desc: '{{ e($item->description ?? '') }}'
                             }">
                            <img src="{{ $imgSrc }}" alt="{{ $item->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-5 overflow-hidden">
                                <div class="text-white translate-y-3 group-hover:translate-y-0 transition-transform duration-300 max-h-full overflow-hidden flex flex-col justify-end w-full">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-[#2FA7FF] mb-1">
                                        {{ $formattedDate }}
                                    </div>
                                    <h3 class="text-base md:text-lg font-heading font-bold uppercase leading-snug line-clamp-1 break-words mb-1">{{ $truncatedTitle }}</h3>
                                    @if($item->description)
                                        <p class="text-xs text-white/80 line-clamp-2 leading-relaxed break-words">{{ $truncatedDesc }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-span-full text-center py-16 reveal">
                        <p class="text-slate-500 text-lg">No photos in the gallery yet. Check back soon for new field uploads.</p>
                    </div>
                @endif
            </div>

            @if(isset($pictures) && method_exists($pictures, 'hasPages') && $pictures->hasPages())
                <div class="mt-12 flex justify-center reveal">
                    {{ $pictures->links() }}
                </div>
            @endif
        </div>

        <!-- Lightbox Modal -->
        <div x-show="activeModal !== null" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="activeModal = null"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
             style="display: none;">
            
            <div class="bg-[#0D1B2A] border border-slate-700/60 rounded-lg max-w-3xl w-full overflow-hidden shadow-2xl relative text-white"
                 @click.away="activeModal = null">
                
                <!-- Modal Close Button -->
                <button @click="activeModal = null" class="absolute top-4 right-4 z-10 w-9 h-9 rounded-full bg-black/60 text-white/80 hover:text-white flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Modal Image Container -->
                <div class="w-full max-h-[60vh] bg-black flex items-center justify-center overflow-hidden">
                    <img :src="activeModal?.src" :alt="activeModal?.title" class="max-h-[60vh] w-auto max-w-full object-contain">
                </div>

                <!-- Modal Body Text -->
                <div class="p-6">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#2FA7FF] mb-1" x-text="activeModal?.date"></div>
                    <h2 class="text-xl md:text-2xl font-heading font-bold uppercase text-white mb-2" x-text="activeModal?.title"></h2>
                    <p x-show="activeModal?.desc" class="text-sm text-slate-300 leading-relaxed max-h-40 overflow-y-auto pr-2" x-text="activeModal?.desc"></p>
                </div>
            </div>
        </div>
    </div>
</x-layout>
