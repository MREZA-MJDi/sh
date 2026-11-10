<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed only application roles and permissions by default.
        // Real academic records are created and maintained through admin CRUD.
        // LocalDemoAccountsSeeder remains an explicit local/testing-only opt-in.
        $this->call([
            RolePermissionSeeder::class,
        ]);
    }
}
