<?php

namespace App\Services\Push;

use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\DeviceToken;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Tells people on their phones when something they are waiting for happens, even with the app
 * closed. Sent after the response (defer), so the admin's or GoBiker's click is never slowed down.
 */
class PushNotifier
{
    /** The kinds a resident can switch off in the app, and their defaults. */
    public const PREFS = [
        'sos_updates' => true,
        'appointments' => true,
        'checkups' => true,
        'sound' => true,
        'vibration' => true,
    ];

    public function __construct(private FcmSender $sender) {}

    // ------------------------------------------------------------ residents

    public function sosStatusChanged(SosRequest $sos): void
    {
        [$title, $body] = match ($sos->status) {
            // Only say a person is coming once one has actually been sent.
            'responding' => $sos->assignedGobiker
                ? ['Someone is Responding', "{$sos->assignedGobiker->name} is on the way to help you."]
                : ['Admin is handling it', 'The Go Bike admin is handling your SOS request.'],
            'resolved' => $sos->resolved_by_gobiker_id
                ? ['SOS resolved', ($sos->resolvedBy?->name ?? 'The GoBiker').' marked your SOS request as resolved. Tap to rate the response.']
                : ['SOS resolved', 'The admin marked your SOS request as resolved.'],
            default => [null, null],
        };
        if ($title) {
            $this->toUser($sos->user, 'sos_updates', $title, $body, ['type' => 'sos', 'id' => (string) $sos->id]);
        }
    }

    public function appointmentChanged(Appointment $appointment): void
    {
        $gobiker = $appointment->gobiker?->name ?? 'A GoBiker';
        $date = $appointment->preferred_date?->format('M j');

        [$title, $body] = match ($appointment->status) {
            'accepted' => ['Home visit accepted', "{$gobiker} will visit you on {$date}."],
            'declined' => ['Home visit declined', $appointment->decline_note ?: 'This visit could not be scheduled. You can ask another GoBiker.'],
            'completed' => ['Home visit done', 'Your check-up is in your Medical History.'],
            default => [null, null],
        };
        if ($title) {
            $this->toUser($appointment->resident, 'appointments', $title, $body, ['type' => 'appointment', 'id' => (string) $appointment->id]);
        }
    }

    /** A GoBiker saved a check-up: tell the resident it belongs to. */
    public function checkupSaved(Patient $patient, bool $updated): void
    {
        $resident = $patient->resident()->first();

        if ($resident) {
            $this->toUser(
                $resident,
                'checkups',
                $updated ? 'Check-up updated' : 'New check-up recorded',
                $updated ? 'A GoBiker updated one of your check-ups.' : 'A GoBiker recorded your check-up. Tap to see the results.',
                ['type' => 'checkup', 'id' => (string) $patient->id],
            );
        }
    }

    // ------------------------------------------------------------- GoBikers

    /** The admin sent a GoBiker to answer a resident's SOS. */
    public function sosAssigned(SosRequest $sos): void
    {
        $where = $sos->address ?: $sos->landmark;

        $this->toUser(
            $sos->assignedGobiker,
            null,
            'SOS: you are assigned',
            ($sos->user?->name ?? 'A resident').' needs help'.($where ? " at {$where}" : '').'. Tap to see the map.',
            ['type' => 'sos_assigned', 'id' => (string) $sos->id],
        );
    }

    /** A resident asked for a home visit: tell the GoBiker they chose. */
    public function newAppointmentRequest(Appointment $appointment): void
    {
        if ($appointment->gobiker) {
            $this->toUser(
                $appointment->gobiker,
                null,
                'Home-visit request for you',
                ($appointment->resident?->name ?? 'A resident').' in '.$appointment->barangay.' asked you for a check-up on '.$appointment->preferred_date?->format('M j').'.',
                ['type' => 'appointment_request', 'id' => (string) $appointment->id],
            );

            return;
        }

        // Older app versions don't choose a GoBiker: tell everyone in the barangay.
        $gobikers = User::where('role', 'GoBiker')
            ->where('status', 'Active')
            ->where('barangay', $appointment->barangay)
            ->get();

        foreach ($gobikers as $gobiker) {
            $this->toUser(
                $gobiker,
                null,
                'New home-visit request',
                "A resident in {$appointment->barangay} asked for a check-up on ".$appointment->preferred_date?->format('M j').'.',
                ['type' => 'appointment_request', 'id' => (string) $appointment->id],
            );
        }
    }

    public function emergencyAnswered(User $gobiker, string $status): void
    {
        [$title, $body] = $status === 'responding'
            ? ['The admin saw your alert', 'The Go Bike admin is handling your emergency.']
            : ['Emergency resolved', 'The admin marked your emergency as resolved.'];

        $this->toUser($gobiker, null, $title, $body, ['type' => 'emergency']);
    }

    /** The admin posted an announcement (or made a hidden one live): tell every GoBiker. */
    public function announcementPosted(Announcement $announcement): void
    {
        // Only those with a phone to send to (there can be many GoBikers without one).
        $gobikers = User::where('role', 'GoBiker')->where('status', 'Active')->whereHas('deviceTokens')->get();

        foreach ($gobikers as $gobiker) {
            $this->toUser(
                $gobiker,
                null,
                'New announcement',
                Str::limit($announcement->title.' · '.$announcement->body, 150),
                ['type' => 'announcement', 'id' => (string) $announcement->id],
            );
        }
    }

    /** The admin decided whether to keep checking a person: tell the GoBikers who checked them. */
    public function followUpDecided(PatientFollowUp $followUp): void
    {
        $title = $followUp->decision === 'stop'
            ? "{$followUp->name} can stop regular check-ups"
            : "Keep checking {$followUp->name}";
        $body = $followUp->note ?: ($followUp->decision === 'stop'
            ? 'The admin says their recent readings are normal.'
            : 'The admin asks you to keep visiting and checking them.');

        $ids = Patient::forPerson($followUp->name, $followUp->contact)->distinct()->pluck('user_id');
        foreach (User::whereIn('id', $ids)->where('role', 'GoBiker')->get() as $gobiker) {
            $this->toUser($gobiker, null, $title, $body, ['type' => 'follow_up']);
        }
    }

    /** A visit the GoBiker was asked for or accepted will not happen. $by: 'resident' or 'admin'. */
    public function appointmentWithdrawn(Appointment $appointment, string $by): void
    {
        $who = $appointment->resident?->name ?? 'The resident';
        $date = $appointment->preferred_date?->format('M j');

        [$title, $body] = $by === 'resident'
            ? ['Home visit cancelled', "{$who} cancelled the visit on {$date}."]
            : ['Visit request closed', "The admin declined {$who}'s visit request for {$date}."];

        $this->toUser($appointment->gobiker, null, $title, $body, ['type' => 'appointment_request', 'id' => (string) $appointment->id]);
    }

    // ------------------------------------------------------------ internals

    /**
     * @param  string|null  $kind  the resident's switch for this kind (null: always send)
     * @param  array<string, string>  $data
     */
    private function toUser(?User $user, ?string $kind, string $title, string $body, array $data): void
    {
        if (! $user || ! $this->sender->enabled()) {
            return;
        }
        $prefs = $user->notificationPrefs();
        if ($kind !== null && ! $prefs[$kind]) {
            return;
        }

        $tokens = DeviceToken::where('user_id', $user->id)->get(['id', 'user_id', 'token', 'updated_at']);
        if ($tokens->isEmpty()) {
            Log::info('Push skipped: no registered phone', ['user_id' => $user->id, 'title' => $title]);

            return;
        }

        // The app made one Android channel per sound/vibration choice.
        $channel = match (true) {
            $prefs['sound'] && $prefs['vibration'] => 'rondago_alerts',
            $prefs['sound'] => 'rondago_sound',
            $prefs['vibration'] => 'rondago_vibrate',
            default => 'rondago_quiet',
        };

        defer(function () use ($tokens, $title, $body, $data, $channel) {
            foreach ($tokens as $device) {
                try {
                    if (! $this->sender->send($device->token, $title, $body, $data, $channel)) {
                        $this->forget($device);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Push notification error: '.$e->getMessage(), ['user_id' => $device->user_id]);
                }
            }
        });
    }

    /**
     * Firebase says this phone is not registered (the app was uninstalled, or it is still being
     * set up). A phone that registered in the last 10 minutes is kept: Firebase can need a moment.
     */
    private function forget(DeviceToken $device): void
    {
        $fresh = $device->updated_at?->gt(now()->subMinutes(10)) ?? false;
        Log::warning($fresh ? 'Push: phone not ready yet, kept' : 'Push: phone not registered, forgotten', [
            'user_id' => $device->user_id,
            'device_token_id' => $device->id,
        ]);

        if (! $fresh) {
            $device->delete();
        }
    }
}
