<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds Pangasinan's full location hierarchy — every city and municipality
 * (48 of them) with their barangays (~1,400) — from the PSGC-derived JSON in
 * storage/app/private, so people from the towns around Bugallon can sign up.
 *
 * Other provinces can be added to the same tables later; the cascade the app
 * walks (Province → City/Municipality → Barangay) reads whatever is seeded.
 *
 * Source dataset: flores-jacob/philippine-regions-provinces-cities-municipalities-barangays
 * (2019 v2, from the PSA PSGC). Run with:
 *   php artisan db:seed --class=PhilippineLocationsSeeder
 *
 * Barangay names repeat across towns ("Poblacion" is 23 towns'), and the
 * barangays.name column is unique, so a repeated name is stored as
 * "Poblacion (Aguilar)". The app stores the plain string either way.
 *
 * Idempotent: re-running adds what is missing and leaves the rest alone.
 */
class PhilippineLocationsSeeder extends Seeder
{
    /** Dataset file, relative to storage/app/private. */
    private const DATASET = 'ph_locations.json';

    private const PROVINCE = 'Pangasinan';

    public function run(): void
    {
        $path = storage_path('app/private/'.self::DATASET);
        abort_unless(File::exists($path), 500, "Missing dataset: {$path}. See the seeder header comment.");

        $regions = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        $province = Province::firstOrCreate(
            ['name' => self::PROVINCE],
            ['region' => 'Ilocos Region (Region I)'],
        );

        // Barangay names repeated across the province's towns, so the seeder
        // knows which need the town appended to stay unique.
        $nameCounts = [];
        foreach ($regions['01']['province_list'][strtoupper(self::PROVINCE)]['municipality_list'] ?? [] as $data) {
            foreach ($data['barangay_list'] ?? [] as $barangay) {
                $name = $this->titleCase($barangay);
                $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;
            }
        }

        foreach ($regions['01']['province_list'][strtoupper(self::PROVINCE)]['municipality_list'] ?? [] as $name => $data) {
            $displayName = $this->titleCase($name);

            // Cities and municipalities sit side by side in the Philippines; the
            // server hangs a municipality row off a city row, so each gets one
            // child municipality of the same name.
            $city = City::firstOrCreate([
                'province_id' => $province->id,
                'name' => $displayName,
            ]);

            $municipality = Municipality::firstOrCreate([
                'city_id' => $city->id,
                'name' => $displayName,
            ]);

            foreach (array_values($data['barangay_list'] ?? []) as $index => $barangay) {
                $barangayName = $this->titleCase($barangay);

                // This town already holds the plain name (the earlier
                // LocationHierarchySeeder seeded Bugallon that way): link that
                // one row instead of adding a suffixed copy beside it.
                $existing = Barangay::where('name', $barangayName)
                    ->where(fn ($q) => $q->where('municipality', $displayName)
                        ->orWhere('municipality_id', $municipality->id))
                    ->first();

                if ($existing) {
                    $existing->update([
                        'sort_order' => $index,
                        'province_id' => $province->id,
                        'city_id' => $city->id,
                        'municipality_id' => $municipality->id,
                    ]);
                    continue;
                }

                // A name repeated across towns needs the town appended to stay
                // unique ("Poblacion" is 23 towns'); a town-unique name is
                // stored plain.
                $name = ($nameCounts[$barangayName] ?? 0) > 1
                    ? "{$barangayName} ({$displayName})"
                    : $barangayName;

                Barangay::firstOrCreate(['name' => $name], [
                    'municipality' => $displayName,
                    'sort_order' => $index,
                    'province_id' => $province->id,
                    'city_id' => $city->id,
                    'municipality_id' => $municipality->id,
                ]);
            }
        }
    }

    /** "BUGALLON" reads "Bugallon" on a phone; ALL CAPS does not. */
    private function titleCase(string $name): string
    {
        // Digits, accents and hyphens survive; each word gets a capital first.
        return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}


