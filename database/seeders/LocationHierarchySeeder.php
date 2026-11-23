<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Database\Seeder;

/**
 * Seeds the location hierarchy (Province → City → Municipality → Barangay) and links the
 * existing barangays (which already hold the Bugallon names) to it.
 *
 * The Philippines layers city and municipality side by side under a province; here a
 * municipality always hangs off a city row that carries the province link, so the app's
 * cascading dropdowns (Province → City/Municipality → Barangay) have one shape everywhere.
 */
class LocationHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // Province: Pangasinan (Region I — Ilocos Region).
        $pangasinan = Province::firstOrCreate(
            ['name' => 'Pangasinan'],
            ['region' => 'Ilocos Region (Region I)'],
        );

        // The "city" tier holds Bugallon, a municipality, until more places are added.
        $bugallonCity = City::firstOrCreate(
            ['province_id' => $pangasinan->id, 'name' => 'Bugallon'],
        );

        $bugallonMunicipality = Municipality::firstOrCreate(
            ['city_id' => $bugallonCity->id, 'name' => 'Bugallon'],
        );

        // Link the barangays seeded with the original migration to the hierarchy. They are
        // matched by name; every one of them belongs to Bugallon.
        Barangay::query()
            ->whereNull('municipality_id')
            ->update([
                'municipality_id' => $bugallonMunicipality->id,
                'city_id' => $bugallonCity->id,
                'province_id' => $pangasinan->id,
            ]);
    }
}