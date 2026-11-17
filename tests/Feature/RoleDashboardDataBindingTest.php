<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleDashboardDataBindingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_library_reads_real_payment_records_and_waiting_provider_state(): void
    {
        $student = $this->userWithRole('student');
        $course = $this->course('student-library-payment', 'paid', 180000);

        Payment::create([
            'reference' => 'SHM-LIBRARY-001',
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 180000,
            'method' => 'gateway',
            'provider' => 'pending',
            'status' => 'awaiting_provider',
        ]);

        $this->actingAs($student)
            ->get(route('student.library'))
            ->assertOk()
            ->assertSee('دوره student-library-payment')
            ->assertSee('در انتظار فعال‌سازی درگاه');
    }

    public function test_teacher_live_workspace_only_lists_online_sessions_and_explains_pending_skyroom(): void
    {
        $teacher = $this->userWithRole('teacher');
        $course = $this->course('teacher-live-sessions');
        $classroom = Classroom::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس مدرس',
            'status' => 'active',
        ]);

        ClassSession::create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه آنلاین قابل نمایش',
            'session_type' => 'online',
            'meeting_provider' => 'skyroom',
            'status' => 'scheduled',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'created_by' => $teacher->id,
        ]);
        ClassSession::create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه حضوری نباید در صفحه آنلاین باشد',
            'session_type' => 'onsite',
            'status' => 'scheduled',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.live'))
            ->assertOk()
            ->assertSee('جلسه آنلاین قابل نمایش')
            ->assertSee('اتصال Skyroom در انتظار فعال‌سازی است')
            ->assertDontSee('جلسه حضوری نباید در صفحه آنلاین باشد');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $role)->value('id'));

        return $user;
    }

    private function course(string $slug, string $type = 'free', int $price = 0): Course
    {
        return Course::create([
            'title' => 'دوره '.$slug,
            'slug' => $slug,
            'type' => $type,
            'price' => $price,
            'status' => 'published',
            'delivery_type' => 'onsite',
        ]);
    }
}
