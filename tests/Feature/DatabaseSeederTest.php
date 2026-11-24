<?php

namespace Tests\Feature;

use App\Models\AcademicGrade;
use App\Models\ClassSession;
use App\Models\Course;
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
        $this->assertDatabaseCount('courses', 51);
        $grade = AcademicGrade::query()->where('code', '10')->firstOrFail();
        $course = Course::query()->where('slug', 'riyazi-takmili-nomre')->firstOrFail();
        $this->assertSame($grade->id, $course->academic_grade_id);
        $this->assertSame('autumn', $course->season);
        $this->assertSame(2, $course->classrooms()->count());
        $this->assertSame(5, ClassSession::query()->whereIn('classroom_id', $course->classrooms()->pluck('id'))
            ->where('session_type', 'online')->count());
        $this->assertTrue($course->spotPlayerVideos()->whereNull('classroom_id')->exists());
        $this->assertDatabaseHas('seo_articles', ['slug' => 'how-to-choose-online-course']);
        $this->assertDatabaseCount('classrooms', 4);
        $this->assertDatabaseCount('guardian_student', 1);
    }

    public function test_production_seed_skips_demo_accounts_and_content(): void
    {
        $this->app['env'] = 'production';
        config([
            'demo.enabled' => true,
            'demo.password' => 'Production-Demo-Only-2026!',
        ]);

        app(DatabaseSeeder::class)->run();
        app(\Database\Seeders\LocalDemoAccountsSeeder::class)->run();

        $this->assertDatabaseMissing('users', ['email' => 'teacher@sh.local']);
        $this->assertDatabaseMissing('users', ['email' => 'student@sh.local']);
        $this->assertDatabaseMissing('courses', ['slug' => 'video-only-demo-course']);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseHas('academic_grades', ['code' => '10']);
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
