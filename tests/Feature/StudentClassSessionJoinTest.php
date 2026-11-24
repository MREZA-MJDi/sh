<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentClassSessionJoinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Carbon::setTestNow('2026-11-14 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_student_can_join_from_fifteen_minutes_before_start_and_click_is_logged(): void
    {
        [$student, $classroom] = $this->studentWithClassroom();
        $session = $this->makeSession($classroom, now()->addMinutes(15), now()->addHour());

        $this->actingAs($student)
            ->post(route('student.class-sessions.join', $session))
            ->assertRedirect('https://meet.example.test/room');

        $this->assertDatabaseHas('class_session_join_logs', [
            'class_session_id' => $session->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_student_cannot_join_earlier_than_fifteen_minutes_before_start(): void
    {
        [$student, $classroom] = $this->studentWithClassroom();
        $session = $this->makeSession($classroom, now()->addMinutes(16), now()->addHour());

        $this->actingAs($student)
            ->from(route('student.dashboard'))
            ->post(route('student.class-sessions.join', $session))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHasErrors('join');

        $this->assertDatabaseCount('class_session_join_logs', 0);
    }

    public function test_student_cannot_join_after_session_end(): void
    {
        [$student, $classroom] = $this->studentWithClassroom();
        $session = $this->makeSession($classroom, now()->subHour(), now()->subMinute());

        $this->actingAs($student)
            ->from(route('student.dashboard'))
            ->post(route('student.class-sessions.join', $session))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHasErrors('join');

        $this->assertDatabaseCount('class_session_join_logs', 0);
    }

    public function test_non_member_cannot_join_another_classroom_session(): void
    {
        [$student, $classroom] = $this->studentWithClassroom(false);
        $session = $this->makeSession($classroom, now()->addMinutes(5), now()->addHour());

        $this->actingAs($student)
            ->post(route('student.class-sessions.join', $session))
            ->assertNotFound();

        $this->assertDatabaseCount('class_session_join_logs', 0);
    }

    public function test_skyroom_session_can_use_a_manually_configured_classroom_link(): void
    {
        [$student, $classroom] = $this->studentWithClassroom();
        $session = $this->makeSession($classroom, now()->addMinutes(5), now()->addHour());
        $session->update(['meeting_provider' => 'skyroom', 'meeting_url' => null]);

        $this->actingAs($student)
            ->post(route('student.class-sessions.join', $session))
            ->assertRedirect('https://meet.example.test/room');

        $this->assertDatabaseHas('class_session_join_logs', [
            'class_session_id' => $session->id,
            'user_id' => $student->id,
        ]);
    }

    private function studentWithClassroom(bool $attach = true): array
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
        $teacher = User::factory()->create();
        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس آزمایشی',
            'status' => 'active',
            'meeting_url' => 'https://meet.example.test/room',
        ]);
        if ($attach) {
            $classroom->students()->attach($student->id);
        }

        return [$student, $classroom];
    }

    private function makeSession(Classroom $classroom, Carbon $startsAt, Carbon $endsAt): ClassSession
    {
        return ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه آنلاین',
            'session_type' => 'online',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'scheduled',
            'meeting_url' => 'https://meet.example.test/room',
        ]);
    }

    public function test_student_can_join_a_jitsi_session_using_its_saved_meeting_url(): void
    {
        [$student, $classroom] = $this->studentWithClassroom();
        $session = $this->makeSession($classroom, now()->addMinutes(5), now()->addHour());
        $session->update([
            'meeting_provider' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/shikhan-student-room-2026',
        ]);

        $this->actingAs($student)
            ->post(route('student.class-sessions.join', $session))
            ->assertRedirect('https://meet.jit.si/shikhan-student-room-2026');

        $this->assertDatabaseHas('class_session_join_logs', [
            'class_session_id' => $session->id,
            'user_id' => $student->id,
        ]);
    }



    public function test_jitsi_session_never_falls_back_to_a_classroom_skyroom_url(): void
    {
        [$student, $classroom] = $this->studentWithClassroom();
        $session = $this->makeSession($classroom, now()->addMinutes(5), now()->addHour());
        $session->update(['meeting_provider' => 'jitsi', 'meeting_url' => null]);

        $this->actingAs($student)
            ->from(route('student.dashboard'))
            ->post(route('student.class-sessions.join', $session))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHasErrors('join');

        $this->assertDatabaseCount('class_session_join_logs', 0);
    }


}
