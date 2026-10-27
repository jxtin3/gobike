<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Juan Dela Cruz',
                'latitude' => 16.0435,
                'longitude' => 120.3334,
                'status' => 'active',
            ],
            [
                'name' => 'Mina Santos',
                'latitude' => 16.0510,
                'longitude' => 120.3440,
                'status' => 'active',
            ],
            [
                'name' => 'Emergency Team 1',
                'latitude' => 16.0388,
                'longitude' => 120.3201,
                'status' => 'responding',
            ],
            [
                'name' => 'Rico Garcia',
                'latitude' => 16.0592,
                'longitude' => 120.3555,
                'status' => 'offline',
            ],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(
                ['name' => $location['name']],
                $location
            );
        }
    }
}
