<x-layout
    title="Go Bike Programming | Go Bike Project"
    description="The complete chapter blueprint — from building partnerships and training Go Bikers to daily operations, risk management and government co-creation."
>
@php
    $journey = [
        ['partnership', 'Partnership Building'],
        ['training', "Go Biker's Training"],
        ['leadership', 'Leadership Training'],
        ['roles', 'Roles'],
        ['activities', 'Activities'],
        ['unit', 'Unit Management'],
        ['alwar', 'ALWAR'],
        ['risk', 'Risk & Conflict'],
        ['cocreation', 'Co-Creation'],
    ];

    $partnershipSteps = [
        ['Orientation with Target Stakeholders', 'SKs, Youth Orgs, Student Orgs, NGOs, GOs, PI'],
        ['MOU Signing', 'PRI x SK x BRGY x LGU x Other NGOs'],
        ['Call for Volunteers', null],
    ];

    $inputs = [
        'PRI Primer & Packages Flyer',
        'Partnership PPT',
        'Memorandum of Understanding',
        'Call for Volunteers Pubmat',
        'Application Forms',
    ];

    $inclusions = [
        ['Certificate of Completion', 'Basic DRRM-CCAM with FA & BLS Level'],
        ['Identification Card', 'Trained and Certified Go Biker accredited by Padyarescue Inc. and Municipal Disaster Risk Reduction and Management Office'],
        ['Premier Bronze Insurance', 'A one-year membership and insurance at Philippine Red Cross'],
        ['Foods & Snacks for 2 Days', 'Fastfood sponsored foods and snacks'],
        ['Training Materials', 'Manual, Ballpen, Triangular Bandage, PPEs'],
    ];

    $leadership = [
        'Selection to Election of Officers',
        'Life Skills & Leadership Training with Team Building Activities',
        'Go Bike Project Operation, System, & Management Orientation',
        'Basic Mental Health & Well Being with Psychological First-Aid and Psychosocial Support Training',
        'Referral System and Community Simulation Exercises and Workshop',
        'Awarding of Go Bike Unit',
    ];

    $roles = [
        ['President', 'Spearheads the Chapter, facilitates decision making processes, formulate strategies and serves as the main focal person of PRI.'],
        ['Vice-President', 'Assists the President in the decision making and the Secretary in the Monitoring, Evaluation, Learning (MEL) processes of the Chapter.'],
        ['Secretary', 'Serves as the secretariat of the Chapter in all its meetings, programs, projects, and activities and immediate MEL Officer of the Go Bikers.'],
        ['Treasurer', 'Manages financial aspects which includes the donations, liquidations of expenses, and finance reportorial of the Chapter.'],
        ['Auditor', 'Reviews and verifies the accuracy of financial records and ensures an effective and efficient financial management of the Chapter.'],
        ['Go Bike Mechanic', 'Manages the Go Bike unit which includes its repair and maintenance. This is where the Go Bike Unit is also placed and stored.'],
        ['Other Volunteer Go Bikers', 'Force multipliers in building a more active, healthier, safer and more disaster resilient community.'],
    ];

    $rotation = ['1st Week: 3 Go Bikers', '2nd Week: 3 Go Bikers', '3rd Week: 3 Go Bikers', '4th Week: 3 Go Bikers', 'Reservists: 3 Go Bikers'];

    $advocacies = ['Disaster Preparedness Campaigns', 'Ronda Kalusugan Program', 'PadyaKaisipan Campaigns', 'Other relevant campaigns and advocacies'];

    $points = [
        'Attendance Monitoring',
        'Impact Assessment (number of people direct and indirect reached)',
        'Verification: Pictures, Attendances, and Patient Data Sheets',
        "Facilitated by Chapter's Go Bike Mechanic and Secretary. Verified by President and Vice President.",
    ];

    $unit = [
        ['Security & Storage', [
            'The Go Bike Unit must have a conducive place for storage. The area should have a cover to protect the bike from the heat of the sun and avoid rusting and further damages from the rain.',
            'The FA Kits, Medicines, and Medical equipments must be regularly checked to avoid spoilage and promote proper disposal and sanitation practices.',
        ]],
        ['Inventory', [
            'A proper inventory management will be religiously conducted by the Chapter Officers-in-charge and ensures to comply to the monthly reportorial to the PRI to access further logistical support when needed.',
        ]],
        ['Repair & Maintenance', [
            'The Go Bike mechanic will be trained on proper repair and maintenance of Go Bike. He/she will also be given with spare essential bike parts and tools in order to fulfill its role. A portion of the donation will also be allocated to the repair, maintenance and or procurement of additional Go Bike unit/s subject to the availability of funds and decision of the majority of the Go Bikers.',
        ]],
        ['Documentation & Reportorial', [
            "The chapter officers must adhere, comply and work harmoniously with Padyarescue Incorporated authorities. Failure to do so is subject for Chapter's disfranchisement and accreditation turn down.",
        ]],
    ];

    $alwar = [
        ['DepEd / School Coordination', 'Entry', []],
        ['ALWAR Training (DRRM-CCAM)', null, []],
        ['ALWAR Leadership Training', null, []],
        ['ALWAR Continuous Trainings', null, ['DRRM', 'Health', 'Education', 'Active Citizenship']],
        ['ALWAR Roll Out Activities', null, []],
        ['ALWAR Monitoring, Evaluation & Planning', null, []],
        ['Transition to Being a Go Biker', 'Exit', []],
    ];

    $risks = [
        [
            ['Lack of active volunteers', 'Conflicts within volunteers', 'Poor leadership and timid officers', 'Corruption issues'],
            'Leadership, personality, communications, and project management trainings',
        ],
        [
            ['Lack of equipment (FA kits, BP apps, RBS kits, medicines)', 'Lack of bike repair materials', 'Lack of office and other operational materials (coupon, ballpen, manila paper, folders, etc.)', 'Lack of IEC materials'],
            'Fund raisings, donations, and forging partnerships',
        ],
        [
            ['Lack of government support', 'Lack of financial support', 'Lack of recognition & rewards', 'Lack of referral support system'],
            'Accreditations, institutionalizations and public private partnerships',
        ],
    ];

    $gov = [
        ['Sangguniang Kabataan', [
            'SK Ordinance accrediting Go Bike Project and appropriating funds thereof via inclusion at the CBYDP and ABYIP Plans of SKs',
            'Logistical support (bikes, FA Kits, medical equipments, IEC materials and others)',
            'Support for the capacity building which includes the continuous trainings and yearly recruitment of Go Bikers',
            'Zero tolerance to any form of corruption through signing a Franchising Policy of both parties',
            'COA and Accounting Offices will serve as the watchdogs of SK x Go Bike Project',
        ]],
        ['Sangguniang Barangay', [
            'BRGY. Ordinance accrediting Go Bikers as certified community responders and appropriating funds thereof via inclusion at the BDRRMFund to support their disaster preparedness activities and emergency response',
            'Employment of Go Bike President as part of the CVO or BHW Group to ensure the commitment and consistency of Go Biker/s',
            'Ensures a strong referral system support to Go Bikers such as during emergency transfers, massive casualty responses, community simulations and drills, etc.',
        ]],
        ['Local Government Unit', [
            'Executive and Legislative Accreditation and appropriating funds thereof via inclusion at the Local Youth Development Plan and LDRRMFund',
            "Accreditation and partnership with MDRRMO to ensure a collective and harmonious efforts in building community's resilience",
            'Small grants for organized Go Bike Chapters to support their PPAs in the communities',
            'Tokens and Recognitions for Outstanding Go Bikers (as recommended by PRI who underwent rigorous validation and monitoring) every end of July in line with the NDRM Celebration',
            'Subsistence for Go Bike Officers and other Go Bike Volunteers',
        ]],
        ['Province & Region', [
            'Scholarship Grants',
            'Food for Work Program',
            'DRRM Advocacy Grants',
            'Rewards and recognitions for Volunteers',
        ]],
    ];

    $private = ['Donations', 'Financial Grants', 'Endowment', 'Small Scale Funding', 'Partnership Events or other PPAs'];
@endphp

<div class="gbp gbp-simple">

    {{-- HERO --}}
    <section class="gbp-hero gbp-hero--photo">
        <div class="gbp-hero-bg" aria-hidden="true">
            <img src="{{ asset('images/whatwedo/gobike.jpg') }}" alt="" fetchpriority="high">
        </div>
        <div class="gbp-hero-shade" aria-hidden="true"></div>

        <div class="gbp-wrap">
            <h1 class="gbp-title">ALWAR x Go Bike Project <em>Programming</em></h1>
            <p class="gbp-lead">
                The complete chapter blueprint — from building partnerships and training Go Bikers
                to daily operations, risk management and government co-creation.
            </p>
            <a href="#full-diagram" class="gbp-hero-link">View the complete diagram</a>
        </div>
    </section>

    {{-- JOURNEY NAV --}}
    <nav class="gbp-journey" aria-label="Programming sections">
        {{-- Touch: native swipe. Mouse: click-and-drag to scroll sideways. --}}
        <ul class="gbp-journey-list"
            x-data="{ down: false, moved: false, startX: 0, startLeft: 0 }"
            x-bind:class="down && moved ? 'is-dragging' : ''"
            x-on:pointerdown="if ($event.pointerType === 'mouse') { down = true; moved = false; startX = $event.clientX; startLeft = $el.scrollLeft }"
            x-on:pointermove="if (down) { const dx = $event.clientX - startX; if (Math.abs(dx) > 5) moved = true; $el.scrollLeft = startLeft - dx }"
            x-on:pointerup.window="down = false"
            x-on:pointerleave="down = false"
            x-on:dragstart.prevent
            x-on:click.capture="if (moved) { $event.preventDefault(); $event.stopPropagation(); moved = false }">
            @foreach ($journey as $j)
                <li>
                    <a href="#{{ $j[0] }}"><b>{{ $loop->iteration }}</b>{{ $j[1] }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- FLOATING RIGHT NAV --}}
    <nav class="gbp-floating-nav" aria-label="Quick jump"
         x-data="{
             show: false,
             activeSection: '',
             sections: {{ json_encode(array_column($journey, 0)) }},
             checkActive() {
                 let current = '';
                 for(let id of this.sections) {
                     const el = document.getElementById(id);
                     if(el && window.scrollY >= (el.offsetTop - 300)) {
                         current = id;
                     }
                 }
                 this.activeSection = current;
             }
         }"
         @scroll.window="show = window.scrollY > 400; checkActive()"
         :class="show ? '' : 'is-hidden'"
         x-cloak>
        <ul class="gbp-floating-list">
            @foreach ($journey as $j)
                <li>
                    <a href="#{{ $j[0] }}" title="{{ $j[1] }}" :class="activeSection === '{{ $j[0] }}' ? 'is-active' : ''">
                        <b>{{ $loop->iteration }}</b>
                        <span class="gbp-float-text">{{ $j[1] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- 1. PARTNERSHIP --}}
    <section id="partnership" class="gbp-section">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">01 · Foundation</span>
                <h2 class="gbp-h2">Partnership Building</h2>
            </header>
            <div class="gbp-split">
                <ol class="gbp-flow">
                    @foreach ($partnershipSteps as $step)
                        <li class="gbp-flow-step">
                            <span class="gbp-num">{{ $loop->iteration }}</span>
                            <div>
                                <h3 class="gbp-h3">{{ $step[0] }}</h3>
                                @if ($step[1])
                                    <p class="gbp-note">{{ $step[1] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
                <aside class="gbp-card">
                    <h3 class="gbp-h3">Inputs</h3>
                    <ul class="gbp-bullets">
                        @foreach ($inputs as $input)
                            <li>{{ $input }}</li>
                        @endforeach
                    </ul>
                </aside>
            </div>
        </div>
    </section>

    {{-- 2. TRAINING --}}
    <section id="training" class="gbp-section is-soft">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">02 · Training</span>
                <h2 class="gbp-h2">Go Biker's Training</h2>
                <p class="gbp-sub">2-Day DRRM-CCAM with FA &amp; BLS Training</p>
            </header>

            <p class="gbp-label">Inclusions</p>
            <div class="gbp-grid gbp-grid-5">
                @foreach ($inclusions as $inc)
                    <article class="gbp-card">
                        <span class="gbp-check"></span>
                        <h3 class="gbp-h3">{{ $inc[0] }}</h3>
                        <p class="gbp-note">{{ $inc[1] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="gbp-price">
                <div><span>1 class</span><strong>15 trainees</strong></div>
                <div class="is-main"><span>Total cost</span><strong>₱30,000.00</strong></div>
                <div><span>Each additional trainee</span><strong>₱2,000</strong></div>
                <p class="gbp-price-note">
                    Complete payment via check should be made to PRI 3 days before the conduct of the
                    training. Official Receipt will be issued by PRI as a donation.
                </p>
            </div>
        </div>
    </section>

    {{-- 3. LEADERSHIP --}}
    <section id="leadership" class="gbp-section">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">03 · Leadership</span>
                <h2 class="gbp-h2">Go Biker's Leadership Training</h2>
                <p class="gbp-sub">2-Day Capacity Building &amp; Continuous Training Program</p>
            </header>
            <ol class="gbp-timeline">
                @foreach ($leadership as $item)
                    <li>
                        <span class="gbp-num">{{ $loop->iteration }}</span>
                        <p>{{ $item }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 4. ROLES --}}
    <section id="roles" class="gbp-section is-soft">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">04 · Structure</span>
                <h2 class="gbp-h2">Go Biker's Roles</h2>
            </header>
            <div class="gbp-oversight">
                <strong>PRI · Go Bike Program Officer/s</strong>
                <span>Conducts monthly monitoring and evaluation with the Go Bike Chapter Officers in any way possible.</span>
            </div>
            <div class="gbp-grid gbp-grid-roles">
                @foreach ($roles as $role)
                    <article class="gbp-card {{ $loop->last ? 'is-wide' : '' }}">
                        <h3 class="gbp-h3">{{ $role[0] }}</h3>
                        <p class="gbp-note">{{ $role[1] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 5. ACTIVITIES --}}
    <section id="activities" class="gbp-section">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">05 · Operations</span>
                <h2 class="gbp-h2">Go Biker's Activities</h2>
            </header>

            <div class="gbp-activity-top">
                <div class="gbp-card">
                    <p class="gbp-label">Regular activity</p>
                    <ul class="gbp-chips">
                        <li>Quarterly Disaster Rescue Drill</li>
                        <li>Quarterly Chapter Meeting</li>
                    </ul>
                </div>
                <div class="gbp-card">
                    <p class="gbp-label">Highly recommended (non-compulsory)</p>
                    <ul class="gbp-chips">
                        <li>Weekly Ronda Activity</li>
                    </ul>
                </div>
            </div>

            <div class="gbp-chain">
                <div class="gbp-chain-box">
                    <h3>Go Bike Chapter</h3>
                    <ul><li>1 Go Bike Unit x 15 Go Bikers</li></ul>
                </div>
                <div class="gbp-chain-box">
                    <h3>Weekly rotation</h3>
                    <ul>@foreach ($rotation as $r)<li>{{ $r }}</li>@endforeach</ul>
                </div>
                <div class="gbp-chain-box">
                    <h3>Ronda advocacies</h3>
                    <ul>@foreach ($advocacies as $a)<li>{{ $a }}</li>@endforeach</ul>
                </div>
                <div class="gbp-chain-box is-award">
                    <h3>Yearly awarding of outstanding Go Bikers</h3>
                </div>
            </div>

            <div class="gbp-grid gbp-grid-2">
                <article class="gbp-card">
                    <h3 class="gbp-h3">Point Reward System</h3>
                    <ul class="gbp-bullets">
                        @foreach ($points as $p)<li>{{ $p }}</li>@endforeach
                    </ul>
                </article>
                <article class="gbp-card">
                    <h3 class="gbp-h3">Donation Management</h3>
                    <ul class="gbp-chips">
                        <li>Chapter's Donation Box</li>
                        <li>Go Biker's Tip Box</li>
                        <li>Go Biker's Pantry</li>
                    </ul>
                    <p class="gbp-note">Regularly monitored by Chapter's President, Treasurer and Auditor.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- 6. UNIT MANAGEMENT --}}
    <section id="unit" class="gbp-section is-soft">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">06 · Assets</span>
                <h2 class="gbp-h2">Go Bike Unit Management</h2>
            </header>
            <div class="gbp-grid gbp-grid-4">
                @foreach ($unit as $u)
                    <article class="gbp-card">
                        <h3 class="gbp-h3">{{ $u[0] }}</h3>
                        @foreach ($u[1] as $para)
                            <p class="gbp-note">{{ $para }}</p>
                        @endforeach
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 7. ALWAR --}}
    <section id="alwar" class="gbp-section">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">07 · Youth Pipeline</span>
                <h2 class="gbp-h2">ALWAR</h2>
                <p class="gbp-sub">Alliance of Learners Working for Altruism and Resilience</p>
            </header>
            <ol class="gbp-alwar">
                @foreach ($alwar as $step)
                    <li class="gbp-card">
                        @if ($step[1])<span class="gbp-tag is-{{ strtolower($step[1]) }}">{{ $step[1] }}</span>@endif
                        <span class="gbp-num">{{ $loop->iteration }}</span>
                        <h3 class="gbp-h3">{{ $step[0] }}</h3>
                        @if (count($step[2]))
                            <ul class="gbp-dash">
                                @foreach ($step[2] as $tag)<li>{{ $tag }}</li>@endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 8. RISK --}}
    <section id="risk" class="gbp-section is-soft">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">08 · Resilience</span>
                <h2 class="gbp-h2">Risk and Conflict Management</h2>
            </header>
            @foreach ($risks as $row)
                <div class="gbp-risk">
                    <div class="gbp-risk-col is-risk">
                        <p class="gbp-risk-tag">Risks</p>
                        <ul class="gbp-bullets">
                            @foreach ($row[0] as $r)<li>{{ $r }}</li>@endforeach
                        </ul>
                    </div>
                    <span class="gbp-risk-arrow" aria-hidden="true">→</span>
                    <div class="gbp-risk-col is-fix">
                        <p class="gbp-risk-tag">Mitigation &amp; Management</p>
                        <p class="gbp-risk-fix-text">{{ $row[1] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 9. CO-CREATION --}}
    <section id="cocreation" class="gbp-section">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">09 · Sustainability</span>
                <h2 class="gbp-h2">Co-Creation</h2>
            </header>

            <p class="gbp-label">Government support</p>
            <div class="gbp-grid gbp-grid-gov">
                @foreach ($gov as $g)
                    <article class="gbp-card">
                        <h3 class="gbp-h3">{{ $g[0] }}</h3>
                        <ul class="gbp-bullets is-small">
                            @foreach ($g[1] as $line)<li>{{ $line }}</li>@endforeach
                        </ul>
                    </article>
                @endforeach
            </div>

            <div class="gbp-private">
                <p class="gbp-label">Private support</p>
                <ul class="gbp-chips">
                    @foreach ($private as $p)<li>{{ $p }}</li>@endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- OUTCOME --}}
    <section class="gbp-outcome">
        <p>Safe, Proactive, Responsive, Healthier, and More <span>Disaster Resilient Communities!</span></p>
    </section>

    {{-- FULL DIAGRAM + LIGHTBOX --}}
    <section id="full-diagram" class="gbp-section is-soft"
             x-data="{ open: false, zoom: false }"
             x-on:keydown.escape.window="open = false; zoom = false">
        <div class="gbp-wrap">
            <header class="gbp-head reveal">
                <span class="gbp-kicker">Reference</span>
                <h2 class="gbp-h2">Complete Programming Diagram</h2>
                <p class="gbp-sub">The original ALWAR x Go Bike Project Programming.</p>
            </header>

            <button type="button" class="gbp-poster" x-on:click="open = true" aria-label="Open the full diagram">
                <img src="{{ asset('images/whatwedo/programming.jpg') }}"
                     alt="ALWAR x Go Bike Project programming diagram" loading="lazy">
            </button>

            <a href="{{ asset('images/whatwedo/programming.jpg') }}" download class="gbp-download">Download</a>
        </div>

        <div class="gbp-lightbox" x-show="open" x-cloak x-transition.opacity
             x-effect="document.body.style.overflow = open ? 'hidden' : ''"
             x-on:click.self="open = false; zoom = false">
            <div class="gbp-lb-bar">
                <span x-text="zoom ? 'Click the image to fit the screen' : 'Click the image to zoom in'"></span>
                <button type="button" class="gbp-lb-close" aria-label="Close"
                        x-on:click="open = false; zoom = false">&times;</button>
            </div>
            <div class="gbp-lb-scroll" x-on:click.self="open = false; zoom = false">
                <img src="{{ asset('images/whatwedo/programming.jpg') }}"
                     alt="ALWAR x Go Bike Project programming diagram (enlarged)"
                     x-bind:class="zoom ? 'is-zoom' : ''"
                     x-on:click="zoom = !zoom">
            </div>
        </div>
    </section>

</div>
</x-layout>
