<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class MessagingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_can_send_a_message_to_a_student_or_linked_guardian_in_an_assigned_class(): void
    {
        Notification::fake();

        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student);
        $course = \App\Models\Course::query()->create([
            'title' => 'ریاضی', 'slug' => 'math-message-workflow', 'status' => 'published',
            'type' => 'free', 'price' => 0,
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id, 'teacher_id' => $teacher->id,
            'name' => 'کلاس ریاضی', 'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $this->actingAs($teacher)->post(route('teacher.messages.store'), [
            'target' => "parent:{$classroom->id}:{$student->id}:{$guardian->id}",
            'subject' => 'پیگیری وضعیت درسی',
            'body' => 'برای هماهنگی جلسه با شما تماس می‌گیریم.',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $teacher->id,
            'recipient_id' => $guardian->id,
            'student_id' => $student->id,
        ]);
        Notification::assertSentTo($guardian, MessageActivityNotification::class);
    }

    public function test_guardian_cannot_read_a_child_thread_where_the_guardian_is_not_a_participant(): void
    {
        $guardian = $this->userWithRole('parent');
        $otherGuardian = $this->userWithRole('parent');
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $guardian->children()->attach($student);

        $message = Message::query()->create([
            'sender_id' => $otherGuardian->id,
            'recipient_id' => $teacher->id,
            'student_id' => $student->id,
            'subject' => 'پیام خصوصی ولی دیگر',
            'body' => 'محتوای خصوصی',
            'status' => 'open',
        ]);

        $this->actingAs($guardian)
            ->get(route('guardian.messages.show', $message))
            ->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));
        return $user;
    }
}