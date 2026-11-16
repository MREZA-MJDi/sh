<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ParentMeeting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'starts_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertDatabaseHas('parent_meetings', [
            'classroom_id' => $classroom->id,
            'guardian_id' => $guardian->id,
            'status' => 'proposed',
        ]);
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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));
        return $user;
    }
}