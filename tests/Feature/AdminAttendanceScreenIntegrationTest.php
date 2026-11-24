<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAttendanceScreenIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_existing_attendance_radio_form_reads_and_writes_the_canonical_database_schema(): void
    {
        $this->travelTo(now()->setTime(12, 0));

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::query()->create([
            'title' => 'دوره آزمون حضور',
            'slug' => 'attendance-screen-regression',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس آزمون حضور',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه regression',
            'session_type' => 'onsite',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'status' => 'completed',
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance.index', [
                'class_id' => $classroom->id,
                'date' => now()->format('Y-m-d'),
                'session' => $session->id,
            ]))
            ->assertOk()
            ->assertSee('name="status['.$student->id.']"', false)
            ->assertSee('data-roster', false)
            ->assertSee('data-t="present"', false);

        $response = $this->actingAs($admin)
            ->post(route('admin.attendance.store'), [
                'class_id' => $classroom->id,
                'date' => now()->format('Y-m-d'),
                'session' => $session->id,
                'status' => [$student->id => 'late'],
                'note' => [$student->id => '۱۲ دقیقه تأخیر'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'classroom_id' => $classroom->id,
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'late',
            'note' => '۱۲ دقیقه تأخیر',
            'marked_by' => $admin->id,
        ]);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('۱۲ دقیقه تأخیر');
    }

    public function test_attendance_screen_renders_without_any_classrooms(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $this->actingAs($admin)
            ->get(route('admin.attendance.index'))
            ->assertOk()
            ->assertSee('برای این روز جلسه‌ای ثبت نشده')
            ->assertSee('موردی برای پیگیری ثبت نشده');
    }

    public function test_report_counts_absences_from_the_existing_attendance_database(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::query()->create([
            'title' => 'دوره گزارش غیبت',
            'slug' => 'attendance-report-regression',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس گزارش غیبت',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        for ($index = 0; $index < 3; $index++) {
            $session = ClassSession::query()->create([
                'classroom_id' => $classroom->id,
                'title' => 'جلسه غیبت '.$index,
                'session_type' => 'onsite',
                'starts_at' => now()->subDays($index + 1),
                'ends_at' => now()->subDays($index + 1)->addHour(),
                'status' => 'completed',
                'created_by' => $teacher->id,
            ]);

            Attendance::query()->create([
                'classroom_id' => $classroom->id,
                'class_session_id' => $session->id,
                'student_id' => $student->id,
                'session_date' => $session->starts_at->toDateString(),
                'status' => 'absent',
                'marked_by' => $admin->id,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.attendance.index', ['class_id' => $classroom->id]))
            ->assertOk()
            ->assertSee('غیبت زیاد')
            ->assertSee('اطلاع به ولی');
    }
}
