<?php

namespace Tests\Feature;

use App\Models\AcademicGrade;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Database\Seeders\AcademicGradeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicGradeCourseMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            AcademicGradeSeeder::class,
        ]);
        $this->withoutVite();
    }


    public function test_academic_grade_seeder_is_idempotent_and_provides_all_twelve_grades(): void
    {
        $this->seed(AcademicGradeSeeder::class);
        $this->seed(AcademicGradeSeeder::class);

        $this->assertSame(12, AcademicGrade::query()->count());
        $this->assertSame(
            range(1, 12),
            AcademicGrade::query()->orderBy('level')->pluck('level')->all()
        );
    }

    public function test_grade_ten_owns_seasonal_courses_with_multiple_classroom_delivery_modes_and_course_videos(): void
    {
        $grade = AcademicGrade::query()->where('code', '10')->firstOrFail();
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student', [
            'grade' => $grade->name,
            'academic_grade_id' => $grade->id,
        ]);

        $course = Course::query()->create([
            'title' => 'ریاضی دهم؛ دوره پاییزه',
            'slug' => 'grade-ten-autumn-math',
            'subject' => 'ریاضی',
            'grade' => $grade->name,
            'academic_grade_id' => $grade->id,
            'season' => 'autumn',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'skyroom',
            'learning_features' => [
                'recorded_video' => true,
                'live_class' => true,
                'resources' => true,
                'assignments' => true,
                'exams' => true,
                'projects' => false,
            ],
            'status' => 'published',
        ]);

        $onlineClass = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'ریاضی دهم آنلاین - گروه یک',
            'status' => 'active',
        ]);
        $onsiteClass = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'ریاضی دهم حضوری - گروه دو',
            'status' => 'active',
        ]);

        $onlineClass->students()->attach($student->id, ['assigned_by' => $teacher->id]);
        $onsiteClass->students()->attach($student->id, ['assigned_by' => $teacher->id]);

        ClassSession::query()->create([
            'classroom_id' => $onlineClass->id,
            'title' => 'جلسه آنلاین فصل اول',
            'session_type' => 'online',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'meeting_provider' => 'skyroom',
            'status' => 'scheduled',
            'created_by' => $teacher->id,
        ]);
        ClassSession::query()->create([
            'classroom_id' => $onsiteClass->id,
            'title' => 'جلسه حضوری فصل اول',
            'session_type' => 'in_person',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
            'status' => 'scheduled',
            'created_by' => $teacher->id,
        ]);

        SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'classroom_id' => null,
            'created_by' => $teacher->id,
            'title' => 'ویدیوی ضبط‌شده مشترک دوره پاییزه',
            'status' => 'draft',
        ]);

        $this->assertSame($grade->id, $course->academicGrade->id);
        $this->assertSame($grade->id, $student->fresh()->academicGrade->id);
        $this->assertTrue($grade->courses()->whereKey($course->id)->exists());
        $this->assertTrue($grade->students()->whereKey($student->id)->exists());
        $this->assertSame(2, $course->classrooms()->count());
        $this->assertSame(2, ClassSession::query()->whereIn('classroom_id', [$onlineClass->id, $onsiteClass->id])->count());
        $this->assertTrue($course->spotPlayerVideos()->whereNull('classroom_id')->exists());
        $this->assertSame('autumn', $course->season);
    }

    public function test_course_creation_maps_the_legacy_persian_grade_field_to_the_grade_entity(): void
    {
        $admin = $this->userWithRole('admin');
        $grade = AcademicGrade::query()->where('code', '10')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'فیزیک دهم؛ دوره پاییزه',
                'description' => 'مبانی حرکت و نیرو',
                'subject' => 'فیزیک',
                'grade' => 'دهم',
                'season' => 'autumn',
                'teacher_id' => null,
                'type' => 'free',
                'price' => 0,
                'delivery_type' => 'skyroom',
                'status' => 'draft',
            ])
            ->assertRedirect();

        $course = Course::query()->where('title', 'فیزیک دهم؛ دوره پاییزه')->firstOrFail();

        $this->assertSame($grade->id, $course->academic_grade_id);
        $this->assertSame('autumn', $course->season);
        $this->assertSame($grade->name, $course->academicGrade->name);
    }


    public function test_admin_course_form_uses_the_canonical_grade_catalog_and_exposes_season(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.courses.create'))
            ->assertOk()
            ->assertSee('name="academic_grade_id"', false)
            ->assertSee('name="season"', false)
            ->assertSee('اول')
            ->assertSee('دوازدهم')
            ->assertSee('بهار')
            ->assertSee('زمستان');
    }


    public function test_academic_content_seeder_builds_all_grade_and_season_combinations_idempotently(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@sh.local']);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->firstOrFail());

        $student = User::factory()->create(['email' => 'student@sh.local']);
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

        $this->seed(AcademicPortalContentSeeder::class);

        $this->assertSame(48, Course::query()->count());
        $this->assertSame(47, Course::query()->where('status', 'draft')->count());

        foreach (AcademicGrade::query()->get() as $grade) {
            foreach (['spring', 'summer', 'autumn', 'winter'] as $season) {
                $this->assertDatabaseHas('courses', [
                    'academic_grade_id' => $grade->id,
                    'season' => $season,
                ]);
            }
        }

        $this->seed(AcademicPortalContentSeeder::class);

        $this->assertSame(48, Course::query()->count());
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());
        return $user;
    }

    public function test_legacy_grade_aliases_resolve_to_one_canonical_academic_grade(): void
    {
        $grade = AcademicGrade::query()->where('code', '10')->firstOrFail();

        $this->assertSame($grade->id, AcademicGrade::resolveIdFromLegacyName('10'));
        $this->assertSame($grade->id, AcademicGrade::resolveIdFromLegacyName('۱۰'));
        $this->assertSame($grade->id, AcademicGrade::resolveIdFromLegacyName('١٠'));
        $this->assertSame($grade->id, AcademicGrade::resolveIdFromLegacyName('پایه دهم'));
    }

}
