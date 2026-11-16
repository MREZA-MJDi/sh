<?php

namespace Tests\\Feature;

use App\\Models\\ClassSession;
use App\\Models\\Classroom;
use App\\Models\\Course;
use App\\Models\\Role;
use App\\Models\\User;
use App\\Services\\Courses\\CourseEnrollmentService;
use Database\\Seeders\\RolePermissionSeeder;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

final class StudentCourseClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_course_page_distinguishes_course_classroom_and_online_session(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره نمونه',
            'slug' => 'sample-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'skyroom',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس عصر',
            'status' => 'active',
            'meeting_url' => 'https://example.invalid/room',
        ]);
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه اول آنلاین',
            'session_type' => 'online',
            'starts_at' => now()->addMinutes(5),
            'ends_at' => now()->addMinutes(65),
            'meeting_provider' => 'skyroom',
            'meeting_url' => null,
            'status' => 'scheduled',
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertOk()
            ->assertSee('کلاس‌ها و جلسه‌های من')
            ->assertSee('کلاس عصر')
            ->assertSee('جلسه اول آنلاین')
            ->assertSee('ورود به کلاس')
            ->assertSee(route('student.class-sessions.join', $session));
    }
}
