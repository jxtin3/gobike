<section class="vol-promo-section">
    <div class="vol-promo-card">
        <div class="vol-promo-content">
            <h2 class="vol-promo-title">Learn to Save Lives <br> in Your Community</h2>
            <p class="vol-promo-lead">
                Learn first aid and emergency preparedness from Go Bike's trained youth
                responders, at your home, school, office or barangay hall.
            </p>

            <ul class="vol-promo-list">
                @foreach ([
                    'Basic first aid and CPR demo, free for 1 session',
                    'Hands-on CPR & BLS training',
                    'Disaster readiness planning',
                    'Certificate of completion on the full module',
                    'Priced per person, from free to ₱999',
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

            <a href="{{ url('/what-we-do#training-plan') }}" class="vol-promo-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
                See Training Plans
            </a>
        </div>

        <div class="vol-promo-photo">

            <img src="{{ asset('images/jointhemovement/responder.jpg') }}"
                 alt="Go Bike responders during a first aid training"
                 loading="lazy">
        </div>
    </div>
</section>
