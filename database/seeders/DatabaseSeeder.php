<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            LocalDemoAccountsSeeder::class,
        ]);

        $productionDemoSeedEnabled = app()->environment('production')
            && config('demo.enabled')
            && config('demo.seed_academic_content');

        if (app()->environment(['local', 'testing']) || $productionDemoSeedEnabled) {
            $this->call(AcademicPortalContentSeeder::class);
        }
    }
}
