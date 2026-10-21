@php
    $imageUrl = $article->image_path ? \App\Support\Media::url($article->image_path) : null;
@endphp
<x-layout
    :title="$article->title.' | Go Bike Project'"
    :description="$article->summary"
    :image="$imageUrl ?? asset('images/gobike-logo.png')"
    :show-chatbot="false"
>
    <x-slot:head>
        <style>
            #site-header.site-header {
                background-color: #0D1B2A !important;
                backdrop-filter: blur(16px) !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
            }
        </style>
    </x-slot:head>

    <article class="bg-white text-slate-900 pt-24 sm:pt-28 md:pt-36 pb-16 md:pb-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            

            <header class="mb-8">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-heading font-bold text-[#0D1B2A] tracking-tight leading-[1.18] mb-5">
                    {{ $article->title }}
                </h1>

                <div class="py-3.5 border-y border-slate-200 text-xs sm:text-sm text-slate-600 font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F97316]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('F d, Y') }}</time>
                </div>

                @if($article->excerpt)
                    <p class="mt-6 text-lg sm:text-xl text-slate-700 leading-relaxed italic border-l-4 border-[#2FA7FF] pl-4 sm:pl-5 bg-slate-50/60 py-3 rounded-r-sm">
                        {{ $article->excerpt }}
                    </p>
                @endif
            </header>

            @if($imageUrl)
                <figure class="mb-10">
                    <div class="overflow-hidden rounded-sm bg-slate-100 shadow-sm border border-slate-100">
                        <img src="{{ $imageUrl }}" alt="{{ $article->title }}" class="w-full h-auto object-cover max-h-[520px]" loading="eager">
                    </div>
                </figure>
            @endif

            <div class="max-w-3xl mx-auto text-slate-800 text-base sm:text-lg leading-[1.85] break-words">
                {!! nl2br(e($article->body)) !!}
            </div>
        </div>
    </article>

    @if($more->isNotEmpty())
        <section class="py-14 md:py-20 bg-[#F8FAFC]" aria-labelledby="more-news-heading">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 id="more-news-heading" class="font-heading text-3xl font-bold uppercase text-[#0D1B2A] mb-8">More News</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($more as $item)
                        <a href="{{ route('news.show', $item->slug) }}" class="bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col rounded-sm overflow-hidden no-underline">
                            <div class="w-full aspect-video overflow-hidden bg-slate-100">
                                <img src="{{ $item->image_path ? \App\Support\Media::url($item->image_path) : asset('images/gobike-logo.png') }}" alt="" class="w-full h-full object-cover" loading="lazy">
                            </div>
                            <div class="p-5">
                                <div class="text-xs font-semibold text-slate-500 mb-2">{{ $item->published_at->format('F d, Y') }}</div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 leading-tight line-clamp-2">{{ $item->title }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layout>
