<?php

namespace App\Services\Push;

use App\Models\Appointment;
use App\Models\DeviceToken;
use App\Models\Patient;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
            'responding' => ['Someone is Responding', 'The Go Bike admin is handling your SOS request.'],
            'resolved' => ['SOS resolved', 'The admin marked your SOS request as resolved.'],
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
            'declined' => ['Home visit declined', $appointment->decline_note ?: 'The admin could not schedule this visit.'],
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

    public function newAppointmentRequest(Appointment $appointment): void
    {
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

    /** The admin gave a home visit to this GoBiker. */
    public function appointmentAssigned(Appointment $appointment): void
    {
        $this->toUser(
            $appointment->gobiker,
            null,
            'Home visit assigned to you',
            ($appointment->resident?->name ?? 'A resident').' in '.$appointment->barangay.' on '.$appointment->preferred_date?->format('M j').'.',
            ['type' => 'appointment_assigned', 'id' => (string) $appointment->id],
        );
    }

    public function emergencyAnswered(User $gobiker, string $status): void
    {
        [$title, $body] = $status === 'responding'
            ? ['The admin saw your alert', 'The Go Bike admin is handling your emergency.']
            : ['Emergency resolved', 'The admin marked your emergency as resolved.'];

        $this->toUser($gobiker, null, $title, $body, ['type' => 'emergency']);
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

        $tokens = DeviceToken::where('user_id', $user->id)->pluck('token', 'id');
        if ($tokens->isEmpty()) {
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
            foreach ($tokens as $id => $token) {
                try {
                    if (! $this->sender->send($token, $title, $body, $data, $channel)) {
                        DeviceToken::whereKey($id)->delete();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Push notification error: '.$e->getMessage());
                }
            }
        });
    }
}
