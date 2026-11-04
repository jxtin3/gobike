<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Who is old enough to use the app.
 *
 * The minimums are one constant per role so a change is one line. Philippine law sets 18 as the
 * age of majority (RA 6809), and the National Privacy Commission's view is that a minor generally
 * cannot give the consent the Data Privacy Act asks for, so a parent or guardian should. The app
 * is open to 13 and over by the owner's decision; that choice, not a legal minimum, is what these
 * numbers record.
 */
final class Age
{
    public const MIN_RESIDENT = 13;

    public const MIN_GOBIKER = 13;

    public static function minimumFor(?string $role): int
    {
        return $role === 'GoBiker' ? self::MIN_GOBIKER : self::MIN_RESIDENT;
    }

    /**
     * Validation rules for a birthdate: a real past date, nobody born before 1900, and old enough.
     * Optional, because accounts and apps from before the field existed send none.
     *
     * @return array<int, string>
     */
    public static function rules(?string $role): array
    {
        return [
            'nullable',
            'date',
            'after:1900-01-01',
            'before_or_equal:'.now()->subYears(self::minimumFor($role))->toDateString(),
        ];
    }

    /** @return array<string, string> */
    public static function messages(?string $role): array
    {
        $minimum = self::minimumFor($role);

        return [
            'birthdate.before_or_equal' => "You must be at least {$minimum} years old to use RondaGo.",
            'birthdate.after' => 'Enter your real date of birth.',
            'birthdate.date' => 'Enter your date of birth.',
        ];
    }

    /** Whole years as of today, or null when there is no birthdate. */
    public static function fromBirthdate(?CarbonInterface $birthdate): ?int
    {
        return $birthdate?->age;
    }
}
