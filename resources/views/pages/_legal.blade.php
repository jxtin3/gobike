{{--
    Shared layout for the Privacy Policy and Terms & Conditions pages.
    Content (title, last updated, sections) is managed in Admin → Operations → Legal.
    Expects: $document (LegalDocument), $active ('privacy'|'terms'), $description, $intro, $topic.
--}}
@php
    $sections = collect($document->sections ?? [])->values()->map(fn ($section, $i) => [
        'id' => 'section-'.($i + 1),
        'title' => trim($section['title'] ?? ''),
        'content' => $section['content'] ?? '',
    ]);
    $tocItems = $sections->filter(fn ($section) => $section['title'] !== '')->values();
    $docs = [
        'privacy' => ['label' => 'Privacy Policy', 'url' => route('privacy-policy')],
        'terms' => ['label' => 'Terms & Conditions', 'url' => route('terms')],
        'cookies' => ['label' => 'Cookie Policy', 'url' => route('cookie-policy')],
    ];
@endphp
<x-layout :title="$document->title.' | Go Bike'" :description="$description">
    <x-slot:head>
        <style>
            #site-header.site-header {
                background-color: #0D1B2A !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            }

            .legal { background: #fff; color: #1E293B; }
            .legal-wrap { max-width: 72rem; margin: 0 auto; padding: 0 1.5rem; }

            /* Header band */
            .legal-head { padding: 8rem 0 2.75rem; border-bottom: 1px solid #E2E8F0; background: #F8FAFC; }
            .legal-tabs {
                display: flex; gap: 1.75rem; margin: 0 0 2.25rem; overflow-x: auto;
                box-shadow: inset 0 -1px 0 #E2E8F0; scrollbar-width: none;
            }
            .legal-tabs a {
                padding: 0.8rem 0; border-bottom: 2px solid transparent;
                color: #64748B; font-size: 0.875rem; font-weight: 600; text-decoration: none; white-space: nowrap;
                transition: color 0.2s, border-color 0.2s;
            }
            .legal-tabs a:hover { color: #0D1B2A; }
            .legal-tabs a.is-active { border-bottom-color: #F97316; color: #0D1B2A; }
            .legal-title {
                margin: 0 0 0.6rem; color: #0D1B2A;
                font-family: var(--font-heading, 'Oswald', sans-serif); font-size: clamp(2.25rem, 5vw, 3.25rem);
                font-weight: 700; line-height: 1.1; text-transform: uppercase;
            }
            .legal-meta { margin: 0; color: #64748B; font-size: 0.875rem; }
            .legal-intro { max-width: 44rem; margin: 1.25rem 0 0; color: #475569; font-size: 1.0625rem; line-height: 1.75; }

            /* Body: contents list + document */
            .legal-body { display: grid; gap: 2.5rem; padding-top: 3rem; padding-bottom: 5rem; }
            @media (min-width: 1024px) {
                .legal-body { grid-template-columns: 15rem minmax(0, 1fr); gap: 4.5rem; padding-top: 3.5rem; }
                .legal-toc { position: sticky; top: 7.5rem; align-self: start; }
            }

            .legal-toc { padding: 1.25rem 1.25rem 1rem; border: 1px solid #E2E8F0; background: #F8FAFC; }
            @media (min-width: 1024px) { .legal-toc { padding: 0; border: 0; background: none; } }
            .legal-toc-label {
                margin: 0 0 0.75rem; color: #64748B; font-size: 0.7rem; font-weight: 700;
                letter-spacing: 0.16em; text-transform: uppercase;
            }
            .legal-toc ol { margin: 0; padding: 0; list-style: none; }
            .legal-toc a {
                display: block; padding: 0.4rem 0 0.4rem 0.9rem; border-left: 2px solid #E2E8F0;
                color: #475569; font-size: 0.875rem; line-height: 1.45; text-decoration: none;
                transition: color 0.2s, border-color 0.2s;
            }
            .legal-toc a:hover { color: #0D1B2A; }
            .legal-toc a.is-active { border-left-color: #F97316; color: #0D1B2A; font-weight: 600; }

            .legal-doc { max-width: 46rem; }
            .legal-section { padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid #F1F5F9; scroll-margin-top: 7rem; }
            .legal-section:last-of-type { border-bottom: 0; }
            .legal-section h2 { margin: 0 0 0.85rem; color: #0D1B2A; font-size: 1.25rem; font-weight: 700; line-height: 1.4; }

            .legal-prose { color: #334155; font-size: 1rem; line-height: 1.8; overflow-wrap: anywhere; }
            .legal-prose p { margin: 0 0 1rem; }
            .legal-prose p:last-child { margin-bottom: 0; }
            .legal-prose ul, .legal-prose ol { margin: 0 0 1rem; padding-left: 1.25rem; }
            .legal-prose ul { list-style: disc; }
            .legal-prose ol { list-style: decimal; }
            .legal-prose li { margin-bottom: 0.4rem; }
            .legal-prose strong, .legal-prose b { color: #0D1B2A; font-weight: 600; }
            .legal-prose a { color: #2563EB; text-decoration: underline; text-underline-offset: 2px; }
            .legal-prose h3 { margin: 1.25rem 0 0.5rem; color: #0D1B2A; font-size: 1.05rem; font-weight: 700; }

            .legal-empty { padding: 3rem 0; color: #64748B; }

            /* Contact box */
            .legal-contact {
                margin-top: 1rem; padding: 1.5rem 1.75rem;
                border: 1px solid #E2E8F0; border-left: 4px solid #F97316; background: #F8FAFC;
            }
            .legal-contact h2 { margin: 0 0 0.4rem; color: #0D1B2A; font-size: 1.125rem; font-weight: 700; }
            .legal-contact p { margin: 0 0 1.1rem; color: #475569; line-height: 1.7; }
            .legal-contact-links { display: flex; flex-wrap: wrap; align-items: center; gap: 1.25rem; }
            .legal-contact-btn {
                display: inline-flex; padding: 0.75rem 1.5rem; background: #0D1B2A; color: #fff;
                font-size: 0.75rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
                text-decoration: none; transition: background-color 0.2s;
            }
            .legal-contact-btn:hover { background: #132D6B; }
            .legal-contact-link { color: #2563EB; font-size: 0.9rem; font-weight: 600; text-decoration: none; }
            .legal-contact-link:hover { text-decoration: underline; }
        </style>
    </x-slot:head>

    <div class="legal">
        <header class="legal-head">
            <div class="legal-wrap">
                <nav class="legal-tabs" aria-label="Legal documents">
                    @foreach ($docs as $key => $doc)
                        <a href="{{ $doc['url'] }}" @class(['is-active' => $key === $active]) @if ($key === $active) aria-current="page" @endif>{{ $doc['label'] }}</a>
                    @endforeach
                </nav>

                <h1 class="legal-title">{{ $document->title }}</h1>
                @if ($document->last_updated)
                    <p class="legal-meta">Last updated: {{ $document->last_updated }}</p>
                @endif
                <p class="legal-intro">{{ $intro }}</p>
            </div>
        </header>

        <div class="legal-wrap legal-body">
            @if ($tocItems->isNotEmpty())
                <aside
                    class="legal-toc"
                    x-data="{
                        current: '',
                        ids: @js($tocItems->pluck('id')),
                        spy() {
                            let found = this.ids[0];
                            for (const id of this.ids) {
                                const el = document.getElementById(id);
                                if (el && el.getBoundingClientRect().top < 160) found = id;
                            }
                            this.current = found;
                        },
                    }"
                    x-init="spy()"
                    @scroll.window.throttle.100ms="spy()"
                >
                    <p class="legal-toc-label">On this page</p>
                    <ol>
                        @foreach ($tocItems as $item)
                            <li>
                                <a href="#{{ $item['id'] }}" :class="current === '{{ $item['id'] }}' ? 'is-active' : ''">{{ $item['title'] }}</a>
                            </li>
                        @endforeach
                    </ol>
                </aside>
            @endif

            <article class="legal-doc">
                @forelse ($sections as $section)
                    <section id="{{ $section['id'] }}" class="legal-section">
                        @if ($section['title'] !== '')
                            <h2>{{ $section['title'] }}</h2>
                        @endif
                        @if (! empty($section['content']))
                            <div class="legal-prose">{!! $document->renderSectionContent($section['content']) !!}</div>
                        @endif
                    </section>
                @empty
                    <p class="legal-empty">No content has been published yet. Please check back later.</p>
                @endforelse

                <div class="legal-contact">
                    <h2>Questions?</h2>
                    <p>
                        If you have any questions about {{ $topic }}, our team is happy to help.
                        Email us at <a href="mailto:gobike@pydn.org" class="legal-contact-link">gobike@pydn.org</a>.
                    </p>
                    <div class="legal-contact-links">
                        <a href="{{ url('/contact') }}" class="legal-contact-btn">Contact us</a>
                        @foreach ($docs as $key => $doc)
                            @if ($key !== $active)
                                <a href="{{ $doc['url'] }}" class="legal-contact-link">Read our {{ $doc['label'] }} &rarr;</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </article>
        </div>
    </div>
</x-layout>
