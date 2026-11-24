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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ClassSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_creates_updates_and_deletes_a_session_for_own_classroom(): void
    {
        [$teacher, $classroom] = $this->teacherWithClassroom('mine');
        $classroom->update(['meeting_url' => 'https://www.skyroom.online/ch/demo']);

        $this->actingAs($teacher)->get(route('admin.classes.sessions.index', $classroom))->assertOk();

        $this->actingAs($teacher)->post(route('admin.classes.sessions.store', $classroom), [
            'title' => 'جلسه اول',
            'session_type' => 'online',
            'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->addHours(2)->format('Y-m-d\\TH:i'),
            'status' => 'scheduled',
        ])->assertRedirect();

        $session = ClassSession::query()->where('classroom_id', $classroom->id)->firstOrFail();
        $this->assertSame('https://www.skyroom.online/ch/demo', $session->meeting_url);

        $this->actingAs($teacher)->patch(route('admin.classes.sessions.update', $session->id), [
            'title' => 'جلسه اول (ویرایش)',
            'session_type' => 'onsite',
            'starts_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'status' => 'scheduled',
        ])->assertRedirect();

        $this->assertSame('جلسه اول (ویرایش)', $session->fresh()->title);
        $this->assertNull($session->fresh()->meeting_url);

        $this->actingAs($teacher)->delete(route('admin.classes.sessions.destroy', $session->id))->assertRedirect();
        $this->assertDatabaseMissing('class_sessions', ['id' => $session->id]);
    }

    public function test_invalid_calendar_date_returns_validation_error(): void
    {
        [$teacher, $classroom] = $this->teacherWithClassroom('invalid-date');

        $this->actingAs($teacher)
            ->getJson(route('admin.classes.sessions.index', ['classroom' => $classroom->id, 'date' => '2026-13-40']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        [$teacher, $classroom] = $this->teacherWithClassroom('range');

        $this->actingAs($teacher)->post(route('admin.classes.sessions.store', $classroom), [
            'title' => 'جلسه نامعتبر',
            'session_type' => 'online',
            'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'ends_at' => now()->format('Y-m-d\TH:i'),
            'status' => 'scheduled',
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_teacher_cannot_touch_another_teachers_sessions(): void
    {
        [$teacher] = $this->teacherWithClassroom('a');
        [, $otherClassroom] = $this->teacherWithClassroom('b');
        $session = ClassSession::query()->create([
            'classroom_id' => $otherClassroom->id, 'title' => 'جلسه دیگری', 'session_type' => 'online',
            'starts_at' => now()->addDay(), 'status' => 'scheduled',
        ]);

        $this->actingAs($teacher)->get(route('admin.classes.sessions.index', $otherClassroom))->assertNotFound();
        $this->actingAs($teacher)->delete(route('admin.classes.sessions.destroy', $session->id))->assertNotFound();
        $this->assertDatabaseHas('class_sessions', ['id' => $session->id]);
    }

    public function test_session_with_attendance_is_not_deleted(): void
    {
        [$teacher, $classroom] = $this->teacherWithClassroom('att');
        $student = User::factory()->create();
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id, 'title' => 'جلسه با حضور', 'session_type' => 'online',
            'starts_at' => now()->subDay(), 'status' => 'completed',
        ]);
        Attendance::query()->create([
            'classroom_id' => $classroom->id, 'class_session_id' => $session->id, 'student_id' => $student->id,
            'session_date' => now()->subDay()->toDateString(), 'status' => 'present', 'marked_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)->delete(route('admin.classes.sessions.destroy', $session->id))
            ->assertSessionHasErrors('session');
        $this->assertDatabaseHas('class_sessions', ['id' => $session->id]);
    }


    public function test_session_with_join_click_is_not_deleted_so_audit_log_is_preserved(): void
    {
        [$teacher, $classroom] = $this->teacherWithClassroom('join-log');
        $student = User::factory()->create();
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه دارای کلیک ورود',
            'session_type' => 'online',
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(55),
            'meeting_url' => 'https://meet.example.test/room',
            'status' => 'scheduled',
        ]);
        DB::table('class_session_join_logs')->insert([
            'class_session_id' => $session->id,
            'user_id' => $student->id,
            'clicked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)->delete(route('admin.classes.sessions.destroy', $session->id))
            ->assertSessionHasErrors('session');

        $this->assertDatabaseHas('class_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('class_session_join_logs', ['class_session_id' => $session->id]);
    }

    public function test_student_cannot_manage_sessions(): void
    {
        [, $classroom] = $this->teacherWithClassroom('stu');
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

        $this->actingAs($student)->post(route('admin.classes.sessions.store', $classroom), [
            'title' => 'x', 'session_type' => 'online', 'starts_at' => now()->addDay()->format('Y-m-d\TH:i'), 'status' => 'scheduled',
        ])->assertForbidden();
    }

    public function test_online_session_can_be_scheduled_without_a_skyroom_room_or_external_url(): void
    {
        [$teacher, $classroom] = $this->teacherWithClassroom('pending-skyroom');

        $this->actingAs($teacher)
            ->post(route('admin.classes.sessions.store', $classroom), [
                'title' => 'جلسه در انتظار اتصال',
                'session_type' => 'online',
                'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->addHour()->format('Y-m-d\TH:i'),
                'status' => 'scheduled',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_sessions', [
            'classroom_id' => $classroom->id,
            'title' => 'جلسه در انتظار اتصال',
            'meeting_url' => null,
            'meeting_provider' => 'skyroom',
            'status' => 'scheduled',
        ]);
    }

    /** @return array{0: User, 1: Classroom} */
    private function teacherWithClassroom(string $key): array
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->firstOrFail());

        $course = Course::query()->create([
            'title' => 'دوره '.$key, 'slug' => 'session-mgmt-'.$key, 'teacher_id' => $teacher->id,
            'type' => 'free', 'price' => 0, 'delivery_type' => 'onsite', 'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id, 'teacher_id' => $teacher->id, 'name' => 'کلاس '.$key, 'status' => 'active',
        ]);

        return [$teacher, $classroom];
    }

    public function test_teacher_can_schedule_a_jitsi_online_session_with_a_manual_room_url(): void
    {
        [$teacher, $classroom] = $this->teacherWithClassroom('jitsi');

        $this->actingAs($teacher)->post(route('admin.classes.sessions.store', $classroom), [
            'title' => 'کلاس آنلاین Jitsi',
            'session_type' => 'online',
            'meeting_provider' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/shikhan-demo-room-2026',
            'starts_at' => now()->addDay()->format('Y-m-d\\TH:i'),
            'ends_at' => now()->addDay()->addHour()->format('Y-m-d\\TH:i'),
            'status' => 'scheduled',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_sessions', [
            'classroom_id' => $classroom->id,
            'title' => 'کلاس آنلاین Jitsi',
            'meeting_provider' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/shikhan-demo-room-2026',
        ]);
    }


}