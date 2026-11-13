<?php

namespace Tests\Feature;

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

    public function test_guardian_can_request_a_meeting_only_for_a_linked_student(): void
    {
        [$guardian, $student] = $this->guardianWithChild();

        $this->actingAs($guardian)->post(route('guardian.meetings.store'), [
            'student_id' => $student->id,
            'title' => 'پیگیری وضعیت آموزشی',
            'meeting_type' => 'online',
            'agenda' => 'بررسی روند کلاس',
        ])->assertRedirect(route('guardian.meetings.index'));

        $this->assertDatabaseHas('parent_meetings', [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'status' => 'requested',
        ]);

        $other = User::factory()->create();
        $other->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $this->from(route('guardian.meetings.index'))
            ->post(route('guardian.meetings.store'), [
                'student_id' => $other->id,
                'title' => 'درخواست غیرمجاز',
                'meeting_type' => 'phone',
            ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('parent_meetings', 1);
    }

    public function test_guardian_cannot_view_or_respond_to_another_guardians_meeting(): void
    {
        [$guardian, $student] = $this->guardianWithChild();
        [$otherGuardian, $otherStudent] = $this->guardianWithChild();

        $meeting = ParentMeeting::query()->create([
            'guardian_id' => $otherGuardian->id,
            'student_id' => $otherStudent->id,
            'title' => 'جلسه خصوصی',
            'meeting_type' => 'in_person',
            'status' => 'proposed',
        ]);

        $this->actingAs($guardian)
            ->post(route('guardian.meetings.respond', $meeting), ['response' => 'accepted'])
            ->assertNotFound();

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'proposed',
        ]);
    }


    public function test_admin_can_create_meeting_for_guardian_and_reject_unlinked_student(): void
    {
        [$guardian, $student] = $this->guardianWithChild();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $this->actingAs($admin)->post(route('admin.parent-meetings.store'), [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'title' => 'جلسه هماهنگی آقای شیخان',
            'meeting_type' => 'in_person',
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('parent_meetings', [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'status' => 'proposed',
            'title' => 'جلسه هماهنگی آقای شیخان',
        ]);

        $unlinked = User::factory()->create();
        $unlinked->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $this->from(route('admin.parent-meetings.index'))
            ->post(route('admin.parent-meetings.store'), [
                'guardian_id' => $guardian->id,
                'student_id' => $unlinked->id,
                'title' => 'جلسه نامعتبر',
                'meeting_type' => 'phone',
            ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseMissing('parent_meetings', ['title' => 'جلسه نامعتبر']);
    }

    public function test_admin_can_schedule_a_parent_meeting_and_guardian_can_accept_it(): void
    {
        [$guardian, $student] = $this->guardianWithChild();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $meeting = ParentMeeting::query()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'title' => 'جلسه پیگیری',
            'meeting_type' => 'phone',
            'status' => 'requested',
        ]);

        $this->actingAs($admin)->patch(route('admin.parent-meetings.update', $meeting), [
            'status' => 'proposed',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->addMinutes(30)->format('Y-m-d H:i:s'),
            'admin_notes' => 'زمان قطعی شد',
        ])->assertRedirect();

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'proposed',
        ]);

        $this->actingAs($guardian)
            ->post(route('guardian.meetings.respond', $meeting), ['response' => 'accepted'])
            ->assertRedirect();

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($admin)->patch(route('admin.parent-meetings.update', $meeting), [
            'status' => 'accepted',
            'scheduled_at' => now()->addDays(4)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(4)->addMinutes(30)->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('parent_meetings', [
            'id' => $meeting->id,
            'status' => 'proposed',
            'guardian_responded_at' => null,
        ]);
    }

    private function guardianWithChild(): array
    {
        $guardian = User::factory()->create();
        $guardian->roles()->attach(Role::query()->where('name', 'parent')->value('id'));

        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $guardian->children()->attach($student->id);

        return [$guardian, $student];
    }
}
