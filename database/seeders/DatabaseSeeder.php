<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolesTableSeeder::class,
            // PayPeriodSeeder::class,
        ]);

        User::create([
            'name' => 'Antony Boguslavskiy',
            'email' => 'antony@gadyamedia.com',
            'password' => Hash::make('Kostyavika1'),
            'role_id' => 1,
        ]);

    }
}
