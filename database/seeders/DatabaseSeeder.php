<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(InitialDataSeeder::class);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                LocalDemoAccountsSeeder::class,
                RegistrationTicketAnnouncementSeeder::class,
                AcademicPortalContentSeeder::class,
                DemoWorkflowContentSeeder::class,
            ]);
        }
    }
}
