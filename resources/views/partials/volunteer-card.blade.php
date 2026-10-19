<section class="vol-promo-section">
    <div class="vol-promo-card">
        <div class="vol-promo-content">
            <h2 class="vol-promo-title">Become a Go Biker <br> in Your Community</h2>
            <p class="vol-promo-lead">
                Join a youth-led network of cyclist-responders delivering health outreach,
                disaster preparedness, and rapid community response across Pangasinan.
            </p>

            <ul class="vol-promo-list">
                @foreach ([
                    'Open to youth aged 13 to 25',
                    'DRRM, First Aid & BLS training',
                    'Certificate & accredited Go Biker ID',
                    'Health outreach & disaster-prep missions',
                    'Leadership & life-skills development',
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

            <a href="{{ route('volunteer') }}" class="vol-promo-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
                Sign Up as a Volunteer
            </a>
        </div>

        <div class="vol-promo-photo">
            {{-- Swap for a different photo if you have one, so it isn't the same as the Replicate card --}}
            <img src="{{ asset('images/jointhemovement/1.jpg') }}"
                 alt="Go Bike volunteers"
                 loading="lazy">
        </div>
    </div>
</section>