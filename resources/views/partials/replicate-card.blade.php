<section class="rep-section">
    <div class="rep-card">
        <div class="rep-content">
            <h2 class="rep-title">How to Replicate Go Bike Project in Your Community</h2>
            <p class="rep-lead">
                Our comprehensive program gives community leaders everything they need to start
                their own GoBike chapter — from recruitment to dispatch coordination to
                operational templates.
            </p>

            <ul class="rep-list">
                @foreach ([
                    'Step-by-step chapter setup guide',
                    'Recruitment & onboarding templates',
                    'Training module library',
                    'Community integration roadmap',
                    'Digital dispatch protocol',
                ] as $item)
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>

            <a href="{{ route('replicate') }}" class="rep-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
                Access the Program Now
            </a>
        </div>

        <div class="rep-photo">
            <img src="{{ asset('images/whatwedo/gbike.jpg') }}"
                 alt="Go Bike Project training participants holding their certificates"
                 loading="lazy">
        </div>
    </div>
</section>
