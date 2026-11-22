<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ParentMeeting;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ParentMeetingNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class ParentMeetingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_guardian_can_request_a_meeting_only_for_a_linked_child(): void
    {
        $guardian = $this->userWithRole('parent');
        $child = $this->userWithRole('student');
        $otherChild = $this->userWithRole('student');
        $guardian->children()->attach($child);

        $this->actingAs($guardian)->post(route('guardian.meetings.store'), [
            'student_id' => $child->id,
            'title' => 'پیگیری وضعیت درسی',
            'message' => 'لطفاً زمان جلسه اعلام شود.',
        ])->assertRedirect(route('guardian.meetings.index', ['child' => $child->id]));

        $this->assertDatabaseHas('parent_meetings', [
            'guardian_id' => $guardian->id,
            'student_id' => $child->id,
            'status' => 'requested',
        ]);

        $this->actingAs($guardian)->post(route('guardian.meetings.store'), [
            'student_id' => $otherChild->id,
            'title' => 'درخواست غیرمجاز',
        ])->assertNotFound();
    }

    public function test_teacher_can_propose_meeting_only_for_an_assigned_class_and_linked_guardian(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student);
        $course = \App\Models\Course::query()->create([
            'title' => 'ریاضی', 'slug' => 'math-parent-meeting', 'status' => 'published',
            'type' => 'free', 'price' => 0,
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id, 'teacher_id' => $teacher->id,
            'name' => 'کلاس ریاضی', 'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $this->actingAs($teacher)->post(route('admin.parent-meetings.store'), [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'title' => 'گفت‌وگو درباره پیشرفت',
            'starts_at' => now()->addDays(2)->format('Y-m-d\\TH:i'),
        ])->assertRedirect();

        $this->actingAs($teacher)
            ->get(route('admin.parent-meetings.index'))
            ->assertOk()
            ->assertSee('گفت‌وگو درباره پیشرفت');

        $this->assertDatabaseHas('parent_meetings', [
            'classroom_id' => $classroom->id,
            'guardian_id' => $guardian->id,
            'status' => 'proposed',
        ]);
    }

    public function test_parent_meeting_lookup_is_scoped_and_returns_only_active_guardians(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $student->update(['name' => 'دانش‌آموز هدف']);
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student);

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس جستجوی جلسه',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $otherTeacher = $this->userWithRole('teacher');
        $otherStudent = $this->userWithRole('student');
        $otherStudent->update(['name' => 'دانش‌آموز خارج از کلاس']);
        $otherClassroom = Classroom::query()->create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'کلاس مدرس دیگر',
            'status' => 'active',
        ]);
        $otherClassroom->students()->attach($otherStudent->id);

        $this->actingAs($teacher)
            ->getJson(route('admin.parent-meetings.lookup', [
                'q' => 'دانش‌آموز',
                'classroom_id' => $classroom->id,
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $student->id, 'name' => $student->name])
            ->assertJsonFragment(['id' => $guardian->id, 'name' => $guardian->name])
            ->assertJsonMissing(['id' => $otherStudent->id, 'name' => $otherStudent->name]);

        $this->actingAs($teacher)
            ->getJson(route('admin.parent-meetings.lookup', [
                'q' => 'خارج',
                'classroom_id' => $otherClassroom->id,
            ]))
            ->assertNotFound();
    }

    public function test_parent_meeting_list_is_scoped_to_the_selected_child(): void
    {
        $guardian = $this->userWithRole('parent');
        $firstChild = $this->userWithRole('student');
        $secondChild = $this->userWithRole('student');
        $guardian->children()->attach([$firstChild->id, $secondChild->id]);

        ParentMeeting::query()->create([
            'student_id' => $firstChild->id,
            'guardian_id' => $guardian->id,
            'title' => 'جلسه مربوط به فرزند اول',
            'status' => 'requested',
        ]);
        ParentMeeting::query()->create([
            'student_id' => $secondChild->id,
            'guardian_id' => $guardian->id,
            'title' => 'جلسه مربوط به فرزند دوم',
            'status' => 'requested',
        ]);

        $this->actingAs($guardian)
            ->get(route('guardian.meetings.index', ['child' => $secondChild->id]))
            ->assertOk()
            ->assertSee('جلسه مربوط به فرزند دوم')
            ->assertDontSee('جلسه مربوط به فرزند اول');
    }


    public function test_teacher_can_complete_a_scheduled_meeting_without_resubmitting_dates(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student);

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس تکمیل جلسه',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $meeting = ParentMeeting::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'title' => 'جلسه قابل تکمیل',
            'starts_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $this->actingAs($teacher)
            ->patch(route('teacher.meetings.update', $meeting), ['status' => 'completed'])
            ->assertRedirect(route('teacher.meetings'));

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'completed',
            'responded_by' => $teacher->id,
        ]);
    }

    public function test_teacher_can_decline_without_a_date_and_cannot_reopen_a_declined_meeting(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student);

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس رد درخواست',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $meeting = ParentMeeting::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'title' => 'درخواست قابل رد',
            'preferred_at' => now()->addDay(),
            'status' => 'requested',
        ]);

        $this->actingAs($teacher)
            ->patch(route('teacher.meetings.update', $meeting), ['status' => 'declined'])
            ->assertRedirect(route('teacher.meetings'));

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'declined',
        ]);

        $this->actingAs($teacher)
            ->patch(route('teacher.meetings.update', $meeting), [
                'status' => 'scheduled',
                'starts_at' => now()->addDays(2)->format('Y-m-d\\TH:i'),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'declined',
        ]);
    }

    public function test_teacher_can_reschedule_an_existing_scheduled_meeting(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student);

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس تغییر زمان',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $meeting = ParentMeeting::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'title' => 'جلسه قابل تغییر زمان',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
        ]);
        $newStart = now()->addDays(3)->startOfMinute();
        $newEnd = $newStart->copy()->addHour();

        $this->actingAs($teacher)
            ->patch(route('teacher.meetings.update', $meeting), [
                'status' => 'scheduled',
                'starts_at' => $newStart->format('Y-m-d\\TH:i'),
                'ends_at' => $newEnd->format('Y-m-d\\TH:i'),
            ])
            ->assertRedirect(route('teacher.meetings'));

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'scheduled',
            'starts_at' => $newStart->format('Y-m-d H:i:s'),
            'ends_at' => $newEnd->format('Y-m-d H:i:s'),
        ]);
    }


    public function test_administrator_can_schedule_and_complete_a_parent_meeting_but_cannot_reopen_it(): void
    {
        $admin = $this->userWithRole('admin');
        $guardian = $this->userWithRole('parent');
        $student = $this->userWithRole('student');
        $guardian->children()->attach($student);

        $meeting = ParentMeeting::query()->create([
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'title' => 'جلسه قابل مدیریت توسط مدیر',
            'status' => 'requested',
        ]);
        $startsAt = now()->addDays(2)->startOfMinute();

        $this->actingAs($admin)
            ->patch(route('admin.parent-meetings.status', $meeting), [
                'status' => 'scheduled',
                'starts_at' => $startsAt->format('Y-m-d\\TH:i'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'scheduled',
            'responded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.parent-meetings.status', $meeting), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.parent-meetings.status', $meeting), [
                'status' => 'scheduled',
                'starts_at' => now()->addDays(3)->format('Y-m-d\\TH:i'),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'completed',
        ]);
    }


    public function test_parent_meeting_request_notifies_administrators_and_the_assigned_teacher(): void
    {
        Notification::fake();

        $admin = $this->userWithRole('admin');
        $teacher = $this->userWithRole('teacher');
        $guardian = $this->userWithRole('parent');
        $student = $this->userWithRole('student');
        $guardian->children()->attach($student);

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس درخواست ولی',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $this->actingAs($guardian)
            ->post(route('guardian.meetings.store'), [
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'title' => 'درخواست جلسه والدین',
                'message' => 'لطفاً برای گفت‌وگو هماهنگ شود.',
                'preferred_at' => now()->addDays(2)->format('Y-m-d\\TH:i'),
            ])
            ->assertRedirect();

        Notification::assertSentTo($admin, ParentMeetingNotification::class);
        Notification::assertSentTo($teacher, ParentMeetingNotification::class);
    }

    public function test_teacher_meeting_proposal_notifies_administrators(): void
    {
        Notification::fake();

        $admin = $this->userWithRole('admin');
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student);

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس پیشنهاد مدرس',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $this->actingAs($teacher)
            ->post(route('admin.parent-meetings.store'), [
                'classroom_id' => $classroom->id,
                'student_id' => $student->id,
                'guardian_id' => $guardian->id,
                'title' => 'پیشنهاد جلسه مدرس',
                'starts_at' => now()->addDays(2)->format('Y-m-d\\TH:i'),
            ])
            ->assertRedirect();

        Notification::assertSentTo($admin, ParentMeetingNotification::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));
        return $user;
    }
}
