<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo accounts are guarded by their own local/testing + opt-in checks.
        // Academic records remain real CRUD data and are not seeded as fake data.
        $this->call([
            RolePermissionSeeder::class,
            LocalDemoAccountsSeeder::class,
        ]);
    }
}
