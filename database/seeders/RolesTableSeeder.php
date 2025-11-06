<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Admin', 'Manager', 'Employee', 'Accountant'];

        foreach ($roles as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}
