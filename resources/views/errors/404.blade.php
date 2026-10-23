<x-layout title="Page Not Found | Go Bike" description="The page you are looking for could not be found." :show-chatbot="false">
    <x-slot:head>
        <style>
            #site-header.site-header {
                background-color: #0D1B2A !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            }

            .nf {
                background: #F8FAFC; text-align: center;
                padding: 10rem 1.5rem 7rem; min-height: 70vh;
                display: flex; align-items: center; justify-content: center;
            }
            .nf-code {
                font-family: var(--font-heading, 'Oswald', sans-serif);
                font-size: clamp(6rem, 18vw, 10rem); font-weight: 700; line-height: 1;
                color: #0D1B2A; margin: 0 0 1rem;
            }
            .nf-title { font-size: clamp(1.5rem, 4vw, 2.25rem); font-weight: 500; color: #64748B; margin: 0 0 .5rem; }
            .nf-text { font-size: 1.125rem; color: #94A3B8; margin: 0 0 .25rem; }
            .nf-sub { font-size: .875rem; color: #0D1B2A; margin: 0 0 2rem; }
            .nf-btn {
                display: inline-block; padding: .75rem 1.75rem; background: #F97316; color: #fff;
                font-size: .75rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase;
                text-decoration: none; transition: background-color .2s;
            }
            .nf-btn:hover { background: #ea580c; }
        </style>
    </x-slot:head>

    <section class="nf">
        <div>
            <h1 class="nf-code">404</h1>
            <p class="nf-title">Oops, we can't find that page!</p>
            <p class="nf-text">The link might be broken,</p>
            <p class="nf-sub">or the page may have been removed.</p>
            <a href="{{ url('/') }}" class="nf-btn">Go back home</a>
        </div>
    </section>
</x-layout>
