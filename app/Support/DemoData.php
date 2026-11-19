<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Donation;
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
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Fake records for presentations (`php artisan demo:seed`). Every fake account and form
 * submission uses EMAIL_DOMAIN and every fake person a MOBILE_PREFIX number, so clear()
 * can remove exactly these and never touch real data.
 */
class DemoData
{
    public const EMAIL_DOMAIN = 'demo.gobike.test';

    /** Demo data seeded before the app was renamed to Go Bike used these domains; clear() still finds it. */
    public const OLD_EMAIL_DOMAINS = ['demo.rondago.test'];

    /** No real Philippine mobile number starts with 0900, so fake people never match real ones. */
    public const MOBILE_PREFIX = '0900';

    /** Demo announcements have no email or mobile, so clear() knows them by these titles. */
    public const ANNOUNCEMENT_TITLES = [
        'General Assembly This Saturday',
        'Heavy Rain Advisory – Stay Safe on Ronda',
        'CPR & BLS Refresher Training',
        'New BP Monitors and Glucose Strips Available',
        'Thank You, GoBikers!',
        'Bike Check-up Day at the Municipal Hall',
        'Medical Mission in Poblacion',
        'New Ronda Routes for December (draft)',
    ];

    /** Narrows a query to rows whose email is a demo address, old or new domain. */
    public static function withDemoEmail(Builder $query, string $column = 'email'): Builder
    {
        return $query->where(function (Builder $q) use ($column) {
            foreach ([self::EMAIL_DOMAIN, ...self::OLD_EMAIL_DOMAINS] as $domain) {
                $q->orWhere($column, 'like', '%@'.$domain);
            }
        });
    }

    public static function exists(): bool
    {
        return self::withDemoEmail(User::query())->exists();
    }

    /**
     * Takes the demo GoBikers off the live map and nothing else: their accounts and every other
     * demo record stay. Returns how many map locations were deleted.
     */
    public static function clearMap(): int
    {
        $gobikerIds = self::withDemoEmail(User::query())->where('role', 'GoBiker')->pluck('id');

        return Location::whereIn('user_id', $gobikerIds)->delete();
    }

    /** Deletes all demo records. Returns how many were removed per kind. */
    public static function clear(): array
    {
        return DB::transaction(function () {
            $mobile = self::MOBILE_PREFIX.'%';
            $userIds = self::withDemoEmail(User::query())->pluck('id');

            return array_filter([
                // Decisions are keyed by name and contact, not linked to a user.
                'follow-up decisions' => PatientFollowUp::where('contact', 'like', $mobile)->delete(),
                // Would only be unlinked (not deleted) when their GoBiker is deleted.
                'map locations' => Location::whereIn('user_id', $userIds)->delete(),
                'appointments' => Appointment::whereIn('resident_id', $userIds)->delete(),
                'check-ups' => Patient::whereIn('user_id', $userIds)->orWhere('contact', 'like', $mobile)->delete(),
                'SOS requests' => SosRequest::whereIn('user_id', $userIds)->delete(),
                'rondas' => Ronda::whereIn('user_id', $userIds)->delete(),
                'GoBiker messages' => GobikerMessage::whereIn('user_id', $userIds)->delete(),
                'users' => User::whereIn('id', $userIds)->delete(),
                'donations' => self::withDemoEmail(Donation::query())->delete(), // payments cascade
                'contact messages' => self::withDemoEmail(ContactMessage::query())->delete(),
                'training requests' => self::withDemoEmail(TrainingRequest::query())->delete(),
                // The classes the demo volunteers are in (before the volunteers themselves).
                'volunteer classes' => VolunteerClass::whereHas('applications', fn ($q) => self::withDemoEmail($q))->delete(),
                'volunteer applications' => self::withDemoEmail(VolunteerApplication::query())->delete(),
                'chapter inquiries' => self::withDemoEmail(ReplicationRequest::query())->delete(),
                'announcements' => Announcement::whereIn('title', self::ANNOUNCEMENT_TITLES)->delete(),
            ]);
        });
    }
}
