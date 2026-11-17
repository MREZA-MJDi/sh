<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\ParentMeeting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ParentMeetingNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class TeacherPortalMeetingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_can_schedule_or_decline_meetings_only_for_assigned_classes(): void
    {
        Notification::fake();

        $teacher = User::factory()->create(['permissions_customized' => true]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));
        $teacher->permissions()->sync(Permission::query()
            ->whereIn('name', ['dashboard.view', 'classes.view.assigned', 'classes.manage.assigned'])
            ->pluck('id'));

        $guardian = $this->userWithRole('parent');
        $student = $this->userWithRole('student');
        $guardian->children()->attach($student);

        $course = Course::query()->create([
            'title' => 'ریاضی', 'slug' => 'teacher-meeting-workflow', 'status' => 'published',
            'type' => 'free', 'price' => 0,
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id, 'teacher_id' => $teacher->id,
            'name' => 'کلاس ریاضی', 'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $meeting = ParentMeeting::query()->create([
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'title' => 'پیگیری وضعیت آموزشی',
            'preferred_at' => now()->addDays(2),
            'status' => 'requested',
        ]);

        $startsAt = now()->addDays(3)->startOfHour();

        $this->actingAs($teacher)
            ->get(route('teacher.meetings'))
            ->assertOk()
            ->assertSee('پیگیری وضعیت آموزشی')
            ->assertSee(route('teacher.meetings.update', $meeting), false)
            ->assertSee(route('logout'), false)
            ->assertSee('خروج');

        $this->actingAs($teacher)
            ->patch(route('teacher.meetings.update', $meeting), [
                'status' => 'scheduled',
                'starts_at' => $startsAt->format('Y-m-d\\TH:i'),
            ])
            ->assertRedirect(route('teacher.meetings'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'scheduled',
            'responded_by' => $teacher->id,
        ]);

        Notification::assertSentTo($guardian, ParentMeetingNotification::class);

        $otherTeacher = User::factory()->create(['permissions_customized' => true]);
        $otherTeacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));
        $otherTeacher->permissions()->sync(Permission::query()
            ->whereIn('name', ['dashboard.view', 'classes.view.assigned', 'classes.manage.assigned'])
            ->pluck('id'));

        $this->actingAs($otherTeacher)
            ->patch(route('teacher.meetings.update', $meeting), [
                'status' => 'declined',
            ])
            ->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
