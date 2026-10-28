<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\DonationPayment;
use App\Models\GobikerMessage;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Models\ReplicationRequest;
use App\Models\Ronda;
use App\Models\SosRequest;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerClass;
use App\Support\Barangays;
use App\Support\DemoData;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Fills the admin with believable fake records for a presentation: every status, tab and
 * filter has some. Rows are written directly, so no push notifications go out.
 * Remove them again with `php artisan demo:clear`.
 */
class DemoSeed extends Command
{
    use ConfirmableTrait;

    protected $signature = 'demo:seed
        {--fresh : Remove the demo data first, then add it again (refreshes the live map)}
        {--gobikers=30 : How many GoBikers (more fill the live map; 6 of the first 30 wait for approval)}
        {--messages=55 : How many contact messages and GoBiker messages (each)}
        {--force : Skip the confirmation on production}';

    protected $description = 'Add fake presentation data (50+ records per admin section). Remove it with demo:clear.';

    private const FIRST_NAMES = [
        'Maria', 'Jose', 'Juan', 'Ana', 'Mark', 'Angelica', 'John Paul', 'Kristine', 'Carlo', 'Jasmine',
        'Miguel', 'Camille', 'Rafael', 'Patricia', 'Joshua', 'Nicole', 'Adrian', 'Bea', 'Paolo', 'Andrea',
        'Gabriel', 'Leah', 'Christian', 'Rhea', 'Daniel', 'Joy', 'Vincent', 'Mae', 'Kenneth', 'Grace',
        'Ramon', 'Lourdes', 'Ernesto', 'Teresita', 'Rogelio', 'Corazon', 'Danilo', 'Erlinda', 'Alfredo', 'Nenita',
    ];

    private const LAST_NAMES = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Ramos', 'Flores', 'Villanueva',
        'Aquino', 'Castillo', 'De Guzman', 'Fernandez', 'Lopez', 'Manalo', 'Navarro', 'Ocampo', 'Pascual', 'Quinto',
        'Rivera', 'Salazar', 'Tolentino', 'Valdez', 'Zamora', 'Abad', 'Bernal', 'Cabrera', 'Domingo', 'Estrada',
    ];

    private const TOWNS = [
        'Bugallon', 'Lingayen', 'Aguilar', 'Mangatarem', 'Labrador', 'Binmaley', 'Dagupan City',
        'San Carlos City', 'Urbiztondo', 'Calasiao', 'Alaminos City', 'Urdaneta City',
    ];

    private Randomizer $rng;

    private int $mobile = 0;

    private int $mail = 0;

    /** @var array<string, list<array{0: float, 1: float}>> street points not used yet, per barangay */
    private array $unusedSpots = [];

    /** @var array<string, array{0: float, 1: float}> the last spot given out, per barangay */
    private array $lastSpot = [];

    private ?int $adminId;

    public function handle(): int
    {
        if (! $this->confirmToProceed('This adds fake presentation data to the live database.')) {
            return self::FAILURE;
        }

        if (DemoData::exists()) {
            if (! $this->option('fresh')) {
                $this->error('Demo data is already there. Run with --fresh to replace it, or demo:clear to remove it.');

                return self::FAILURE;
            }
            DemoData::clear();
        }

        // Same data every time, so a rehearsal matches the presentation.
        $this->rng = new Randomizer(new Mt19937(2026));
        $this->adminId = User::where('is_admin', true)->value('id');

        DB::transaction(function () {
            [$gobikers] = $this->gobikers(); // the 6 waiting for approval have no activity yet
            $residents = $this->residents();
            // The first 24 carry the detailed history (residents' check-ups, home visits);
            // every GoBiker also gets 0 to 5 check-ups of their own.
            $core = array_slice($gobikers, 0, 24);
            $this->mapAndRondas($gobikers);
            $people = $this->checkups($core, $residents);
            $this->gobikerCheckups($gobikers);
            $this->followUps($people);
            $this->appointments($core, $residents);
            $this->sos($residents);
            $this->gobikerMessages($gobikers);
            $this->announcements();
            $this->donations();
            $this->contactMessages();
            $this->trainingRequests();
            $this->volunteers();
            $this->chapterInquiries();
        });

        $this->table(['Section', 'Demo records'], [
            ['Users (residents + GoBikers)', User::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->count()],
            ['Check-ups', Patient::where('contact', 'like', DemoData::MOBILE_PREFIX.'%')->count()],
            ['Appointments', $this->demoCount(Appointment::class, 'resident_id')],
            ['SOS requests', $this->demoCount(SosRequest::class, 'user_id')],
            ['Rondas', $this->demoCount(Ronda::class, 'user_id')],
            ['GoBiker messages', $this->demoCount(GobikerMessage::class, 'user_id')],
            ['Donations', Donation::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->count()],
            ['Contact messages', ContactMessage::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->count()],
            ['Training requests', TrainingRequest::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->count()],
            ['Volunteer applications', VolunteerApplication::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->count()],
            ['Chapter inquiries', ReplicationRequest::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->count()],
            ['Announcements', Announcement::whereIn('title', DemoData::ANNOUNCEMENT_TITLES)->count()],
        ]);
        $this->info('Done. Remove it all later with: php artisan demo:clear');

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------ people

    /** @return array{0: list<User>, 1: list<User>} active GoBikers, and ones waiting for approval */
    private function gobikers(): array
    {
        $password = Hash::make(Str::random(40)); // nobody can sign in as a demo account
        $active = $pending = [];
        foreach (range(1, max(30, (int) $this->option('gobikers'))) as $i) {
            // Numbers 25-30 signed up in the app and wait for the admin's approval.
            $waiting = $i > 24 && $i <= 30;
            $user = $this->user('gobiker', 'GoBiker', Barangays::ALL[($i - 1) % count(Barangays::ALL)], $password, $waiting ? 'Inactive' : 'Active');
            if ($waiting) {
                $pending[] = $user;
            } else {
                $active[] = $user;
            }
        }

        return [$active, $pending];
    }

    /** @return list<User> */
    private function residents(): array
    {
        $password = Hash::make(Str::random(40));

        return array_map(
            fn ($i) => $this->user('resident', 'User', Barangays::ALL[($i * 7) % count(Barangays::ALL)], $password, 'Active'),
            range(1, 45),
        );
    }

    private function user(string $kind, string $role, string $barangay, string $password, string $status): User
    {
        $created = $this->daysAgo(10, 120);

        return User::forceCreate([
            'name' => $this->name(),
            'email' => $kind.(++$this->mail).'@'.DemoData::EMAIL_DOMAIN,
            'mobile' => $this->mobile(),
            'barangay' => $barangay,
            'email_verified_at' => $created,
            'password' => $password,
            'role' => $role,
            'status' => $status,
            'created_at' => $created,
            'updated_at' => $created,
        ]);
    }

    // ------------------------------------------------------------- operations

    /** Live map pins and ronda history. The first 8 GoBikers are out on ronda right now. */
    private function mapAndRondas(array $gobikers): void
    {
        foreach ($gobikers as $n => $gobiker) {
            // The first 24 have a full history; the extra ones (--gobikers) a shorter one.
            foreach (range(1, $n < 24 ? $this->int(3, 6) : $this->int(1, 3)) as $_) {
                $start = $this->daysAgo(1, 60)->setTime($this->int(6, 18), $this->pick([0, 15, 30, 45]));
                Ronda::forceCreate([
                    'user_id' => $gobiker->id, 'barangay' => $gobiker->barangay,
                    'started_at' => $start, 'ended_at' => $start->copy()->addMinutes($this->int(45, 240)),
                ]);
            }

            // 8 of the first 24 are out now, and about 3 in 4 of the extra ones.
            $onRonda = $n < 8 || ($n >= 24 && $this->chance(75));
            $start = $onRonda ? now()->subMinutes($this->int(10, 120)) : $this->daysAgo(1, 5)->setTime(8, 0);
            if ($onRonda) {
                Ronda::forceCreate(['user_id' => $gobiker->id, 'barangay' => $gobiker->barangay, 'started_at' => $start]);
            }
            [$lat, $lng] = $this->spotIn($gobiker->barangay);
            Location::forceCreate([
                'user_id' => $gobiker->id,
                'name' => $gobiker->name,
                'designated_barangay' => $gobiker->barangay,
                'latitude' => $lat,
                'longitude' => $lng,
                // No emergencies: they would ring the admin alarm during the presentation.
                'status' => 'active',
                'last_seen_at' => $onRonda ? now() : $start->copy()->addHours(3),
                'active_start_time' => $start,
                'active_end_time' => $onRonda ? null : $start->copy()->addHours(3),
            ]);
        }
    }

    /**
     * Check-ups for 50 people (30 of them residents with an account), 1 to 5 visits each over the
     * past year. Each abnormal reading appears for several people; the rest stay normal, and
     * those with 3+ normal visits get the "could stop checking" suggestion.
     *
     * @return list<array{name: string, contact: string, normal: bool}>
     */
    private function checkups(array $gobikers, array $residents): array
    {
        $issues = array_merge(array_keys(Patient::READINGS), array_fill(0, 10, null));
        $people = [];

        foreach (range(0, 49) as $i) {
            $resident = $residents[$i] ?? null;
            $barangay = $resident?->barangay ?? $this->pick(Barangays::ALL);
            $person = [
                'name' => $resident?->name ?? $this->name(),
                'contact' => $resident?->mobile ?? $this->mobile(),
                'barangay' => $barangay,
                'address' => 'Purok '.$this->int(1, 7).', '.$barangay,
                'age' => $this->int(19, 86),
                'issue' => $issues[$i % count($issues)],
            ];
            $person['normal'] = $person['issue'] === null;
            $visits = $person['normal'] ? $this->int(3, 5) : $this->int(1, 4);

            foreach (range(1, $visits) as $v) {
                // The newest visit shows the person's issue; older ones sometimes do too.
                $abnormal = ! $person['normal'] && ($v === $visits || $this->chance(40));
                $this->checkup($person, $this->pick($gobikers), $this->daysAgo(($visits - $v) * 45 + 1, ($visits - $v) * 45 + 40), $abnormal ? $person['issue'] : null);
            }
            $people[] = $person;
        }

        return $people;
    }

    private function checkup(array $person, User $gobiker, Carbon $when, ?string $issue): Patient
    {
        return Patient::forceCreate([
            'user_id' => $gobiker->id,
            'barangay' => $person['barangay'],
            'name' => $person['name'],
            'address' => $person['address'],
            'contact' => $person['contact'],
            'age' => $person['age'],
            'remarks' => $this->remarks($issue),
            'recorded_at' => $when,
            'created_at' => $when,
            'updated_at' => $when,
        ] + $this->vitals($issue));
    }

    /** Normal readings, with the one named by $issue (a Patient::READINGS key) out of range. */
    private function vitals(?string $issue): array
    {
        $v = [
            'sys' => $this->int(108, 128), 'dia' => $this->int(68, 82), 'pulse' => $this->int(64, 92),
            'resp' => $this->int(14, 18), 'temp' => $this->int(363, 372) / 10, 'blood_sugar' => $this->chance(70) ? $this->int(82, 128) : null,
        ];
        match ($issue) {
            'bp_high' => [$v['sys'], $v['dia']] = [$this->int(145, 178), $this->int(92, 108)],
            'bp_low' => [$v['sys'], $v['dia']] = [$this->int(78, 88), $this->int(48, 58)],
            'pulse_high' => $v['pulse'] = $this->int(104, 128),
            'pulse_low' => $v['pulse'] = $this->int(44, 56),
            'resp_high' => $v['resp'] = $this->int(22, 28),
            'resp_low' => $v['resp'] = $this->int(8, 11),
            'temp_high' => $v['temp'] = $this->int(376, 395) / 10,
            'temp_low' => $v['temp'] = $this->int(345, 358) / 10,
            'sugar_high' => $v['blood_sugar'] = $this->int(150, 260),
            'sugar_low' => $v['blood_sugar'] = $this->int(52, 66),
            default => null,
        };

        return $v;
    }

    private function remarks(?string $issue): ?string
    {
        return $issue
            ? $this->pick(['Advised to see the RHU doctor.', 'Rechecked after 10 minutes, still high.', 'Reminded to take maintenance medicine.', 'Family informed.', 'Referred to the barangay health worker.'])
            : ($this->chance(25) ? $this->pick(['No complaints.', 'Feeling well.', 'Regular monthly check.']) : null);
    }

    /**
     * 0 to 5 check-ups for every active GoBiker, of people in their barangay, spread so every
     * report and analytics filter has data: this week and month, the past year, and each of
     * the last 5 years. About 4 in 10 have an abnormal reading. Saved in bulk (there are thousands).
     */
    private function gobikerCheckups(array $gobikers): void
    {
        $issues = [...array_keys(Patient::READINGS), ...array_fill(0, 15, null)];
        $rows = [];
        foreach ($gobikers as $gobiker) {
            $seen = [];
            for ($k = 0, $count = $this->int(0, 5); $k < $count; $k++) {
                // Sometimes a follow-up visit to someone they checked before.
                if ($seen && $this->chance(25)) {
                    $person = $this->pick($seen);
                } else {
                    $seen[] = $person = [
                        'name' => $this->name(),
                        'contact' => $this->mobile(),
                        'address' => 'Purok '.$this->int(1, 7).', '.$gobiker->barangay,
                        'age' => $this->int(18, 90),
                    ];
                }
                $issue = $this->pick($issues);
                $when = $this->checkupDate();
                $rows[] = [
                    'user_id' => $gobiker->id,
                    'barangay' => $gobiker->barangay,
                    ...$person,
                    ...$this->vitals($issue),
                    'remarks' => $this->remarks($issue),
                    'recorded_at' => $when,
                    'created_at' => $when,
                    'updated_at' => $when,
                ];
            }
            if (count($rows) >= 500) {
                Patient::insert($rows);
                $rows = [];
            }
        }
        if ($rows) {
            Patient::insert($rows);
        }
    }

    /** This week, this or last month, the past year, or up to 5 years back. */
    private function checkupDate(): Carbon
    {
        $roll = $this->int(1, 100);

        return match (true) {
            $roll <= 15 => now()->subMinutes($this->int(30, 7 * 24 * 60)),
            $roll <= 40 => now()->subDays($this->int(1, 60))->setTime($this->int(7, 17), $this->int(0, 59)),
            $roll <= 70 => now()->subDays($this->int(61, 365))->setTime($this->int(7, 17), $this->int(0, 59)),
            default => now()->subDays($this->int(366, 5 * 365))->setTime($this->int(7, 17), $this->int(0, 59)),
        };
    }

    /** Admin decisions on some people: keep checking, or stop. */
    private function followUps(array $people): void
    {
        $abnormal = array_values(array_filter($people, fn ($p) => ! $p['normal']));
        $normal = array_values(array_filter($people, fn ($p) => $p['normal']));
        $decided = [
            ...array_map(fn ($p) => [$p, 'continue', 'Reading still high. Visit again next week.'], array_slice($abnormal, 0, 5)),
            ...array_map(fn ($p) => [$p, 'stop', 'Three normal check-ups in a row.'], array_slice($normal, 0, 4)),
        ];
        foreach ($decided as [$person, $decision, $note]) {
            PatientFollowUp::forceCreate([
                'person_key' => Patient::makePersonKey($person['name'], $person['contact']),
                'name' => $person['name'], 'contact' => $person['contact'],
                'decision' => $decision, 'note' => $note,
                'decided_by' => $this->adminId, 'decided_at' => $this->daysAgo(1, 20),
            ]);
        }
    }

    /** Home-visit requests in every status. A resident has at most one open request. */
    private function appointments(array $gobikers, array $residents): void
    {
        $plan = [...array_fill(0, 12, 'pending'), ...array_fill(0, 12, 'accepted'), ...array_fill(0, 18, 'completed'), ...array_fill(0, 10, 'declined'), ...array_fill(0, 8, 'cancelled')];
        $reasons = ['Blood pressure check for my mother, she cannot walk far.', 'My father is bedridden and needs his BP monitored.', 'Follow-up check after my hospital discharge.', 'Blood sugar check, I am diabetic.', 'My lola has been dizzy since yesterday.', 'Regular check for my grandfather (85 years old).', 'Pregnant, requesting a BP check.', 'Recovering from a fever, want a check-up.'];

        foreach ($plan as $i => $status) {
            $open = in_array($status, Appointment::OPEN, true);
            $resident = $open ? $residents[$i] : $this->pick($residents); // 24 open requests, 24 different residents
            $requested = $open ? now()->subHours($this->int(2, 72)) : $this->daysAgo(5, 90);
            $date = $open ? now()->addDays($this->int(0, 10)) : $requested->copy()->addDays($this->int(1, 4));
            // The resident chose this GoBiker, usually one from their own barangay.
            $local = array_values(array_filter($gobikers, fn (User $g) => $g->barangay === $resident->barangay));
            $gobiker = $status === 'cancelled' && $this->chance(30) ? null : $this->pick($local && $this->chance(80) ? $local : $gobikers);
            $accepted = in_array($status, ['accepted', 'completed'], true);
            $done = $status === 'completed' ? $date->copy()->setTime($this->int(8, 16), 30) : null;
            $checkup = $done ? $this->checkup(
                ['name' => $resident->name, 'contact' => $resident->mobile, 'barangay' => $resident->barangay, 'address' => 'Purok '.$this->int(1, 7).', '.$resident->barangay, 'age' => $this->int(40, 85)],
                $gobiker, $done, $this->chance(30) ? 'bp_high' : null,
            ) : null;

            Appointment::forceCreate([
                'resident_id' => $resident->id,
                'barangay' => $resident->barangay,
                'address' => 'Purok '.$this->int(1, 7).', '.$resident->barangay,
                'preferred_date' => $date->toDateString(),
                'preferred_window' => $this->pick(array_keys(Appointment::WINDOWS)),
                'reason' => $this->pick($reasons),
                'status' => $status,
                'gobiker_id' => $gobiker?->id,
                'patient_id' => $checkup?->id,
                'decline_note' => $status === 'declined' ? $this->pick(["{$gobiker->name} can't make this visit. You can ask another GoBiker.", 'I am out of town that day, sorry po.', 'Please go to the RHU, this needs a doctor.', 'My bike is under repair this week.']) : null,
                'accepted_at' => $accepted ? $requested->copy()->addHours($this->int(1, 20)) : null,
                'completed_at' => $done,
                'created_at' => $requested,
                'updated_at' => $done ?? $requested,
            ]);
        }
    }

    /** SOS requests: some being answered, most resolved. None waiting, so the admin alarm stays quiet. */
    private function sos(array $residents): void
    {
        $plan = [...array_fill(0, 10, 'responding'), ...array_fill(0, 50, 'resolved')];
        $landmarks = ['Beside the barangay hall', 'Near the chapel', 'In front of the sari-sari store', 'Across the elementary school', 'Near the basketball court', 'Beside the rice mill', 'Behind the health center', 'Near the waiting shed'];
        $what = ['My father fell and cannot stand.', 'Lola is having trouble breathing.', 'A motorcycle accident on the road.', 'My child has a very high fever and is shaking.', 'My neighbor fainted.', 'Deep cut on the hand from a bolo.', 'Chest pain, he is sweating a lot.', 'Elderly woman collapsed at the market.', null];

        foreach ($plan as $status) {
            $resident = $this->pick($residents);
            $sent = $status === 'sent' ? now()->subMinutes($this->int(2, 25)) : ($status === 'responding' ? now()->subMinutes($this->int(20, 180)) : $this->daysAgo(1, 75));
            $byResident = $status === 'resolved' && $this->chance(20);
            $answered = $status !== 'sent' && ! $byResident;
            $gps = $this->chance(85); // some phones do not share their position
            [$lat, $lng] = $this->spotIn($resident->barangay);

            SosRequest::forceCreate([
                'user_id' => $resident->id,
                'municipality' => Barangays::MUNICIPALITY,
                'barangay' => $resident->barangay,
                'address' => $this->int(1, 250).' '.$this->pick(['Rizal St.', 'Mabini St.', 'Bonifacio St.', 'Luna St.', 'Del Pilar St.']).', Purok '.$this->int(1, 7),
                'landmark' => $this->pick($landmarks),
                'description' => $this->pick($what),
                'latitude' => $gps ? $lat : null,
                'longitude' => $gps ? $lng : null,
                'status' => $status,
                'handled_by' => $answered ? $this->adminId : null,
                'responded_at' => $answered ? $sent->copy()->addMinutes($this->int(1, 8)) : null,
                'resolved_at' => $status === 'resolved' ? $sent->copy()->addMinutes($this->int(20, 90)) : null,
                'resolved_by_resident' => $byResident,
                'created_at' => $sent,
                'updated_at' => $sent,
            ]);
        }
    }

    /** Messages GoBikers sent the admin from the app; the newest ~10% are unread. */
    private function gobikerMessages(array $gobikers): void
    {
        $templates = [
            'Ronda done in {brgy}, all clear.',
            'Ronda done in Purok {purok}, {brgy}. Checked {n} residents.',
            'Checked {n} seniors today in {brgy}, {k} had high BP.',
            'Checked {n} people at the barangay hall, all normal readings.',
            'Need more BP cuffs, mine is broken.',
            'My digital thermometer is not working, can I get a new one?',
            'Low on alcohol and cotton in my kit.',
            'Running out of glucose strips, only {k} left.',
            'Road to Purok {purok} is flooded, used the other route.',
            'Road in {brgy} is muddy after the rain, ronda took longer.',
            'Can someone cover my ronda tomorrow morning? I have an exam.',
            'Flat tire, will be late for the afternoon ronda.',
            'Bike chain broke, I walked the rest of the ronda.',
            'Resident in Purok {purok} asked about the next medical mission.',
            'A lola in {brgy} asked if we can visit her every week.',
            'Assisted a fall victim near the market, brought to the RHU.',
            'Helped a motorcycle accident victim on the highway, ambulance came.',
            'Barangay captain of {brgy} says thank you to the team!',
            'Kagawad asked if we can join the clean-up drive on Saturday.',
            'Will be absent tomorrow, I have a fever.',
            'Back on duty today, ready for ronda in {brgy}.',
            'Dog bite case in Purok {purok}, advised to go to the RHU for anti-rabies.',
            'Resident with BP {sys}/{dia}, advised to see a doctor today.',
            'Done with {n} home visits this week.',
            'Is there a refresher training this month?',
            'GPS on my phone was off for a while, sorry. Fixed now.',
            'Heavy rain here in {brgy}, staying at the barangay hall for now.',
            'Delivered the medicines to the seniors in Purok {purok}.',
        ];
        $count = max(55, (int) $this->option('messages'));
        $unread = max(15, intdiv($count, 10));

        foreach (range(1, $count) as $i) {
            $gobiker = $this->pick($gobikers);
            $new = $i <= $unread; // the newest ones are still unread
            $sent = $new ? now()->subMinutes($this->int(5, 3 * 24 * 60)) : $this->daysAgo(3, 60);
            GobikerMessage::forceCreate([
                'user_id' => $gobiker->id,
                'message' => strtr($this->pick($templates), [
                    '{brgy}' => $gobiker->barangay, '{purok}' => $this->int(1, 7),
                    '{n}' => $this->int(4, 18), '{k}' => $this->int(1, 4),
                    '{sys}' => $this->int(150, 180), '{dia}' => $this->int(95, 110),
                ]),
                'is_read' => ! $new,
                'created_at' => $sent, 'updated_at' => $sent,
            ]);
        }
    }

    /**
     * Announcements GoBikers see on their dashboard: five live (the newest a few hours old, so it
     * shows as "New"), two that ended and one hidden draft, so each status shows in the admin.
     */
    private function announcements(): void
    {
        $saturday = now()->next(Carbon::SATURDAY);
        $training = now()->addDays(11);
        [$assembly, $rain, $cpr, $supplies, $thanks, $bikes, $mission, $routes] = DemoData::ANNOUNCEMENT_TITLES;

        $items = [
            [$thanks, 'This month you recorded more than 800 check-ups across all 24 barangays of Bugallon. The Barangay Captains and the Municipal Health Office thank every GoBiker for your service. Keep up the good work, and ride safe!',
                true, now()->endOfMonth(), now()->subDays(6)],
            [$supplies, 'Thanks to our donors, we received 20 new digital BP monitors and glucose test strips. GoBikers whose monitors are broken or low on strips can claim new ones at the Go Bike office, Monday to Friday, 8:00 AM to 5:00 PM.',
                true, null, now()->subDays(4)],
            [$cpr, 'A refresher training on CPR and Basic Life Support will be held on '.$training->format('F j').' at Bugallon National High School, 8:00 AM to 4:00 PM. This is required for all GoBikers who joined before '.now()->year.'. A certificate will be given after the training. Message the admin to confirm your slot.',
                true, $training->copy()->setTime(17, 0), now()->subDays(2)],
            [$rain, 'PAGASA has raised a heavy rainfall warning for Pangasinan until '.now()->addDays(3)->format('l').'. Avoid flooded roads, especially near the Agno River and low-lying puroks. If the road is not safe, end your ronda early and report through the app. Your safety comes first.',
                true, now()->addDays(3)->endOfDay(), now()->subDay()],
            [$assembly, 'All GoBikers are invited to our monthly general assembly this Saturday, '.$saturday->format('F j').', 9:00 AM at the Bugallon Municipal Hall. We will talk about next month\'s rondas, new first aid kits, and the upcoming medical mission. Please bring your Go Biker ID. Snacks will be served.',
                true, $saturday->copy()->setTime(12, 0), now()->subHours(3)],
            [$bikes, 'Free bike check-up for all GoBikers at the Municipal Hall parking area, 8:00 AM to 12:00 NN. Our partner mechanics will check brakes, tires and chains.',
                true, now()->subDays(9), now()->subDays(20)],
            [$mission, 'Medical mission at the Poblacion covered court with the Municipal Health Office. GoBikers in Poblacion, Salasa and Bacabac, please help guide residents and record BP readings.',
                true, now()->subDays(25), now()->subDays(35)],
            [$routes, 'Draft: each barangay will have two ronda routes in December, morning and afternoon. The admin will share the final routes after the general assembly.',
                false, null, now()->subDays(1)],
        ];

        foreach ($items as [$title, $body, $active, $expires, $created]) {
            Announcement::forceCreate([
                'title' => $title, 'body' => $body, 'is_active' => $active, 'expires_at' => $expires,
                'created_by' => $this->adminId, 'created_at' => $created, 'updated_at' => $created,
            ]);
        }
    }

    // ---------------------------------------------------------- website inbox

    private function donations(): void
    {
        foreach (range(1, 60) as $i) {
            $method = $this->pick(['paypal', 'paypal', 'gcash', 'bank']);
            $monthly = $method === 'paypal' && $this->chance(35);
            $status = $this->pick(['paid', 'paid', 'paid', 'paid', 'pending', 'failed', 'cancelled']);
            $usd = $this->pick([5, 10, 10, 15, 20, 25, 50, 50, 100, 200]);
            $abroad = $method === 'paypal' && $this->chance(40);
            $org = $this->chance(20);
            [$first, $last] = $this->nameParts();
            $created = $this->daysAgo(0, 150);
            $subStatus = $monthly ? ($status === 'paid' ? $this->pick(['active', 'active', 'active', 'suspended', 'cancelled', 'expired']) : 'cancelled') : null;
            $email = $this->email('donor');

            $donation = Donation::forceCreate([
                'donor_type' => $org ? 'organization' : 'individual',
                'org_name' => $org ? $this->pick(['Lingayen Rotary Club', 'Pangasinan Medical Society', 'St. Andrew Parish', 'Bugallon Rice Traders Assoc.', 'Kabataan Para sa Bayan']) : null,
                'first_name' => $first, 'last_name' => $last,
                'email' => $email, 'phone' => $this->mobile(),
                'address1' => $this->int(1, 300).' '.$this->pick(['Rizal St.', 'Maple Ave.', 'Quezon Blvd.', 'Main St.']),
                'city' => $abroad ? $this->pick(['Los Angeles', 'Toronto', 'Dubai', 'Sydney']) : $this->pick(self::TOWNS),
                'postcode' => $abroad ? (string) $this->int(10000, 99999) : '2416',
                'state' => $abroad ? $this->pick(['California', 'Ontario', 'Dubai', 'NSW']) : 'Pangasinan',
                'country' => $abroad ? $this->pick(['US', 'CA', 'AE', 'AU']) : 'PH',
                'amount_usd' => $usd,
                'amount_php' => $method === 'paypal' ? null : $usd * 58,
                'platform_fee_usd' => $this->chance(50) ? round($usd * 0.05, 2) : 0,
                'frequency' => $monthly ? 'monthly' : 'once',
                'payment_method' => $method,
                'status' => $status,
                'paypal_order_id' => $method === 'paypal' && ! $monthly ? 'DEMO-ORDER-'.$i : null,
                'paypal_subscription_id' => $monthly ? 'DEMO-SUB-'.$i : null,
                'subscription_status' => $subStatus,
                'cancelled_at' => $subStatus === 'cancelled' ? $created->copy()->addDays($this->int(5, 60)) : null,
                'paymongo_session_id' => $method !== 'paypal' ? 'demo_cs_'.$i : null,
                'paymongo_payment_id' => $method !== 'paypal' && $status === 'paid' ? 'demo_pay_'.$i : null,
                'receipt_email' => $email,
                'created_at' => $created, 'updated_at' => $created,
            ]);

            if ($status === 'paid') {
                foreach (range(1, $monthly ? $this->int(1, 4) : 1) as $k) {
                    DonationPayment::forceCreate([
                        'donation_id' => $donation->id,
                        'gateway' => $method === 'paypal' ? 'paypal' : 'paymongo',
                        'gateway_payment_id' => "demo-{$i}-{$k}",
                        'amount' => $method === 'paypal' ? $usd : $usd * 58,
                        'currency' => $method === 'paypal' ? 'USD' : 'PHP',
                        'paid_at' => $created->copy()->addMonths($k - 1),
                    ]);
                }
            }
        }
    }

    /** Contact-form messages from the website; the newest ~10% are unread. */
    private function contactMessages(): void
    {
        $bySubject = [
            'volunteer' => [
                'Hi! I would like to help Go Bike in {town}. I have my own bike. How can I join?',
                'Our youth group in {town} wants to help with your health outreach. Who can we talk to?',
                'I am a nursing student from {school}. Can I help during your medical missions?',
            ],
            'partnership' => [
                'Our company would like to partner with Go Bike for our CSR program this year.',
                'The SK of {brgy} would like to partner with you for a first aid seminar.',
                'We are a cooperative in {town} and want to sponsor bikes for your GoBikers.',
                'Our school would like to sign a partnership for student first aid training.',
            ],
            'media' => [
                'I am a reporter from a local station. Can we feature your volunteers this month?',
                'Hello, I am writing a story about youth responders in Pangasinan. Can I interview your team?',
                'Our campus paper at {school} would like to feature Go Bike. Are you available next week?',
            ],
            'donation' => [
                'How can we donate bikes and BP monitors to your chapter?',
                'We collected {n} first aid kits from our office. Where can we drop them off?',
                'Can I donate through GCash every month? I could not find the option.',
                'Our family wants to donate in memory of our lola. How do we get a receipt?',
            ],
            'program' => [
                'Can Go Bike hold a first aid training in our school next month?',
                'Do you do home visits in {brgy}? My father needs regular BP checks.',
                'How much is the training for {n} people in our barangay hall?',
                'Is the Basic training really free? We are {n} in the family.',
                'When is the next Ronda Kalusugan in {brgy}?',
            ],
            'others' => [
                'Thank you to the GoBiker who helped my father last week in {brgy}!',
                'Salamat po sa GoBiker na tumulong sa aking lola kahapon.',
                'Your website does not load the gallery on my phone, just letting you know.',
                'I lost my ID during your event in {town}, did anyone find it?',
            ],
        ];
        $count = max(55, (int) $this->option('messages'));
        $unread = max(15, intdiv($count, 10));

        foreach (range(1, $count) as $i) {
            $new = $i <= $unread; // the newest ones are still unread
            $subject = $this->pick(array_keys($bySubject));
            $sent = $new ? now()->subMinutes($this->int(5, 4 * 24 * 60)) : $this->daysAgo(4, 120);
            ContactMessage::forceCreate([
                'name' => $this->name(), 'email' => $this->email('contact'), 'phone' => $this->chance(70) ? $this->mobile() : null,
                'subject' => $subject,
                'other_concern' => $subject === 'others' ? $this->pick(['Thank-you note', 'Website problem', 'Lost item', 'Feedback']) : null,
                'message' => strtr($this->pick($bySubject[$subject]), [
                    '{town}' => $this->pick(self::TOWNS), '{brgy}' => $this->pick(Barangays::ALL), '{n}' => $this->int(5, 30),
                    '{school}' => $this->pick(['Pangasinan State University', 'Bugallon National High School', 'Lyceum-Northwestern University', 'Universidad de Dagupan']),
                ]),
                'is_read' => ! $new,
                'created_at' => $sent, 'updated_at' => $sent,
            ]);
        }
    }

    private function trainingRequests(): void
    {
        foreach (range(0, 59) as $i) {
            $created = $this->daysAgo(0, 100);
            TrainingRequest::forceCreate([
                'name' => $this->name(), 'email' => $this->email('training'), 'phone' => $this->mobile(),
                'address' => 'Purok '.$this->int(1, 7).', '.$this->pick(Barangays::ALL).', '.$this->pick(self::TOWNS).', Pangasinan',
                'tier' => TrainingRequest::OFFERED[$i % 2],
                // Requests still open train on a coming date; completed ones trained in the past.
                'preferred_date' => ($status = TrainingRequest::STATUSES[intdiv($i, 3) % count(TrainingRequest::STATUSES)]) === 'completed'
                    ? $created->copy()->addDays($this->int(2, 10))->min(now()->subDay())->toDateString()
                    : now()->addDays($i % 9 === 0 ? 0 : $this->int(1, 45))->toDateString(),
                'preferred_window' => $this->pick(['morning', 'afternoon', null]),
                'message' => $this->pick(['We are 5 in the family, weekends please.', 'For our office staff, around 12 people.', 'Our youth group wants to learn CPR.', 'Preferred schedule: Saturday morning.', null]),
                'status' => $status,
                'created_at' => $created, 'updated_at' => $created,
            ]);
        }
    }

    /**
     * Volunteers in every stage: one class already trained, one class coming up, a
     * full class waiting (22, so the admin can schedule the next class), 16 new sign-ups to review and rejected ones.
     */
    private function volunteers(): void
    {
        $past = VolunteerClass::forceCreate([
            'starts_on' => now()->subDays(40)->toDateString(), 'venue' => 'Bugallon Municipal Hall',
            'notes' => '8:00 AM to 5:00 PM, both days.', 'completed_at' => now()->subDays(38),
        ]);
        $next = VolunteerClass::forceCreate([
            'starts_on' => now()->addDays(12)->toDateString(), 'venue' => 'Bugallon National High School gym',
            'notes' => '8:00 AM to 5:00 PM. Bring a valid ID and drinking water.',
        ]);

        $plan = [
            ...array_fill(0, 24, ['trained', $past, [45, 90]]),
            ...array_fill(0, 22, ['scheduled', $next, [15, 40]]),
            ...array_fill(0, 22, ['waiting', null, [2, 30]]),
            ...array_fill(0, 16, ['pending', null, [0, 3]]),
            ...array_fill(0, 6, ['rejected', null, [5, 60]]),
        ];

        foreach ($plan as [$status, $class, [$fromDays, $toDays]]) {
            [$first, $last] = $this->nameParts();
            $occupation = $this->pick(['student', 'student', 'working', 'out-of-school', 'other']);
            $age = $occupation === 'student' ? $this->int(15, 22) : $this->int(18, 25);
            $minor = $age < 18;
            $created = $this->daysAgo($fromDays, $toDays);
            VolunteerApplication::forceCreate([
                'first_name' => $first, 'last_name' => $last, 'email' => $this->email('volunteer'), 'phone' => $this->mobile(),
                'age' => $age, 'occupation_status' => $occupation,
                'school' => $occupation === 'student' ? $this->pick(['Pangasinan State University', 'Bugallon National High School', 'Lyceum-Northwestern University', 'Universidad de Dagupan']) : null,
                'barangay' => $this->pick(Barangays::ALL), 'municipality' => $this->pick(self::TOWNS), 'province' => 'Pangasinan',
                'availability' => $this->pick(['weekdays', 'weekends', 'both', 'flexible']),
                'motivation' => $this->pick(['I want to help the seniors in our barangay.', 'I am a nursing student and want real experience.', 'Go Bike helped my lolo before, I want to give back.', 'I love biking and helping people.', null]),
                'emergency_name' => $this->name(), 'emergency_phone' => $this->mobile(),
                'guardian_name' => $minor ? $this->name() : null, 'guardian_phone' => $minor ? $this->mobile() : null, 'guardian_consent' => $minor,
                'application_status' => $status,
                'volunteer_class_id' => $class?->id,
                // Demo addresses are not real: mark them emailed so nobody sends to them.
                'class_emailed_at' => $class ? $class->created_at : null,
                'created_at' => $created, 'updated_at' => $created,
            ]);
        }
    }

    private function chapterInquiries(): void
    {
        $orgs = [
            'sk' => 'SK of Barangay ', 'barangay' => 'Barangay Council of ', 'lgu' => 'Municipal Government of ',
            'school' => 'National High School of ', 'youth_org' => 'Kabataan Volunteers of ', 'ngo' => 'Bayanihan Foundation ', 'other' => 'Parish Youth Ministry of ',
        ];
        foreach (range(0, 54) as $i) {
            $type = array_keys($orgs)[$i % count($orgs)];
            $town = $this->pick(self::TOWNS);
            $trainees = $this->int(10, 50);
            $payment = ReplicationRequest::PAYMENT_STATUSES[$i % count(ReplicationRequest::PAYMENT_STATUSES)];
            $created = $this->daysAgo(0, 120);
            ReplicationRequest::forceCreate([
                'organization' => $orgs[$type].($type === 'sk' || $type === 'barangay' ? $this->pick(Barangays::ALL) : $town),
                'organization_type' => $type,
                'contact_name' => $this->name(),
                'position' => $this->pick(['SK Chairperson', 'Barangay Captain', 'MDRRMO Head', 'School Principal', 'President', null]),
                'email' => $this->email('chapter'), 'phone' => $this->mobile(),
                'municipality' => $town, 'province' => 'Pangasinan',
                'trainees' => $trainees,
                'preferred_date' => $this->chance(80) ? now()->addDays($this->int(-30, 90))->toDateString() : null,
                'message' => $this->pick(['We want to start a Go Bike chapter in our town.', 'Our SK has budget for this year, please send details.', 'Can the training be held on two Saturdays?', null]),
                'amount_php' => config('replication.class_fee') + max(0, $trainees - config('replication.class_size')) * config('replication.additional_trainee_fee'),
                'payment_status' => $payment,
                'paymongo_session_id' => $payment !== 'unpaid' ? 'demo_cs_chapter_'.$i : null,
                'paymongo_payment_id' => in_array($payment, ['paid', 'refunded'], true) ? 'demo_pay_chapter_'.$i : null,
                'paid_at' => in_array($payment, ['paid', 'refunded'], true) ? $created->copy()->addDay() : null,
                'status' => ReplicationRequest::STATUSES[intdiv($i, 4) % count(ReplicationRequest::STATUSES)],
                'created_at' => $created, 'updated_at' => $created,
            ]);
        }
    }

    // ---------------------------------------------------------------- helpers

    /**
     * A spot on a real street of the barangay. database/data/bugallon-street-points.json holds
     * points along Bugallon's streets (from OpenStreetMap, near each barangay's centre), so
     * nobody stands in a river or a rice field. About 1 in 3 joins the previous GoBiker of the
     * same barangay, so some ride in small groups and the rest alone.
     *
     * @return array{0: float, 1: float}
     */
    private function spotIn(?string $barangay): array
    {
        $points = self::streetPoints();
        $barangay = isset($points[$barangay]) ? $barangay : 'Poblacion';

        if (isset($this->lastSpot[$barangay]) && $this->chance(30)) {
            [$lat, $lng] = $this->lastSpot[$barangay];

            return [$lat + $this->float(-0.00012, 0.00012), $lng + $this->float(-0.00012, 0.00012)];
        }

        // Use every street point once before reusing any.
        if (empty($this->unusedSpots[$barangay])) {
            $this->unusedSpots[$barangay] = $this->rng->shuffleArray($points[$barangay]);
        }

        return $this->lastSpot[$barangay] = array_pop($this->unusedSpots[$barangay]);
    }

    /** @return array<string, list<array{0: float, 1: float}>> */
    private static function streetPoints(): array
    {
        static $points;

        return $points ??= json_decode(file_get_contents(database_path('data/bugallon-street-points.json')), true);
    }

    private function demoCount(string $model, string $userColumn): int
    {
        return $model::whereIn($userColumn, User::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->select('id'))->count();
    }

    private function name(): string
    {
        return implode(' ', $this->nameParts());
    }

    /** @return array{0: string, 1: string} */
    private function nameParts(): array
    {
        return [$this->pick(self::FIRST_NAMES), $this->pick(self::LAST_NAMES)];
    }

    private function mobile(): string
    {
        return DemoData::MOBILE_PREFIX.str_pad((string) ++$this->mobile, 7, '0', STR_PAD_LEFT);
    }

    private function email(string $kind): string
    {
        return $kind.(++$this->mail).'@'.DemoData::EMAIL_DOMAIN;
    }

    private function daysAgo(int $min, int $max): Carbon
    {
        return now()->subDays($this->int($min, $max))->subMinutes($this->int(0, 600));
    }

    private function pick(array $items): mixed
    {
        return $items[$this->rng->getInt(0, count($items) - 1)];
    }

    private function int(int $min, int $max): int
    {
        return $this->rng->getInt($min, $max);
    }

    private function float(float $min, float $max): float
    {
        return round($min + ($max - $min) * $this->rng->getInt(0, 1_000_000) / 1_000_000, 7);
    }

    private function chance(int $percent): bool
    {
        return $this->rng->getInt(1, 100) <= $percent;
    }
}
