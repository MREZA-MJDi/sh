<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AcademicPortalContentSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_seed_is_repeatable_and_creates_a_complete_demo_portal(): void
    {
        config([
            'demo.enabled' => true,
            'demo.password' => 'Local-Demo-Password-2026!',
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@sh.local']);
        $this->assertDatabaseHas('users', ['email' => 'teacher@sh.local']);
        $this->assertDatabaseHas('users', ['email' => 'student@sh.local']);
        $this->assertDatabaseHas('users', ['email' => 'parent@sh.local']);
        $this->assertDatabaseHas('courses', ['slug' => 'riyazi-takmili-nomre']);
        $this->assertDatabaseHas('courses', ['slug' => 'riyazi-pishrafte-dore-pooli']);
        $this->assertDatabaseHas('courses', ['slug' => 'video-only-demo-course']);
        $this->assertDatabaseHas('courses', ['slug' => 'hybrid-live-demo-course']);
        $this->assertDatabaseCount('courses', 4);
        $this->assertDatabaseHas('seo_articles', ['slug' => 'how-to-choose-online-course']);
        $this->assertDatabaseCount('classrooms', 3);
        $this->assertDatabaseCount('guardian_student', 1);
    }

    public function test_production_demo_seed_requires_both_explicit_flags_and_is_repeatable(): void
    {
        $this->app['env'] = 'production';

        config([
            'demo.enabled' => true,
            'demo.seed_academic_content' => true,
            'demo.password' => 'Production-Demo-Only-2026!',
        ]);

        app(DatabaseSeeder::class)->run();
        app(DatabaseSeeder::class)->run();

        $this->assertDatabaseHas('users', ['email' => 'teacher@sh.local', 'is_active' => true]);
        $this->assertDatabaseHas('users', ['email' => 'student@sh.local', 'is_active' => true]);
        $this->assertDatabaseHas('courses', ['slug' => 'riyazi-takmili-nomre']);
        $this->assertDatabaseHas('courses', ['slug' => 'riyazi-pishrafte-dore-pooli']);
        $this->assertDatabaseHas('courses', ['slug' => 'video-only-demo-course']);
        $this->assertDatabaseHas('courses', ['slug' => 'hybrid-live-demo-course']);
        $this->assertDatabaseHas('seo_articles', ['slug' => 'how-to-choose-online-course']);
        $this->assertDatabaseCount('courses', 4);
        $this->assertDatabaseCount('classrooms', 3);
    }

    public function test_academic_demo_seeder_never_attaches_fixtures_to_unrelated_local_users(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $teacher = User::factory()->create(['email' => 'real.teacher@example.test']);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $student = User::factory()->create(['email' => 'real.student@example.test']);
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $this->seed(AcademicPortalContentSeeder::class);

        $this->assertDatabaseMissing('courses', ['slug' => 'riyazi-takmili-nomre']);
        $this->assertDatabaseMissing('courses', ['slug' => 'riyazi-pishrafte-dore-pooli']);
        $this->assertDatabaseMissing('classrooms', ['name' => 'کلاس ریاضی تکمیلی - گروه اول']);
    }
}
