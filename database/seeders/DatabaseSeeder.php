<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Core roles and permissions are available in every environment.
        // Review accounts are created only by the guarded local/testing seeder.
        $this->call([
            RolePermissionSeeder::class,
            LocalDemoAccountsSeeder::class,
        ]);
    }
}
