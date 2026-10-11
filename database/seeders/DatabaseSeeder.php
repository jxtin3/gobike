<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Skipped: demo seed data only runs when APP_ENV=local.');

            return;
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@onegobike.com',
            'password' => bcrypt('admin123'),
            'is_admin' => true,
        ]);

        $this->call([
            LocationSeeder::class,
        ]);
    }
}