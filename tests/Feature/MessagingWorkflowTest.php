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
        // A child may have multiple linked guardians; linkage grants dashboard visibility,
        // not access to another guardian's private message thread.
        $otherGuardian->children()->attach($student);

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

    public function test_guardian_can_message_an_assigned_teacher_and_read_the_reply(): void
    {
        Notification::fake();

        $teacher = $this->userWithRole('teacher');
        $otherTeacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student);

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس فعال',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student);

        $payload = [
            'student_id' => $student->id,
            'subject' => 'پیگیری تکلیف',
            'body' => 'لطفاً وضعیت تکلیف را بررسی کنید.',
        ];

        $this->actingAs($guardian)
            ->post(route('guardian.messages.store'), $payload + ['recipient_id' => $otherTeacher->id])
            ->assertForbidden();
        $this->assertDatabaseCount('messages', 0);

        $response = $this->post(route('guardian.messages.store'), $payload + ['recipient_id' => $teacher->id]);
        $message = Message::query()->sole();
        $response->assertRedirect(route('guardian.messages.show', $message));

        $reply = 'تکلیف بررسی و نمره ثبت شد.';
        $this->actingAs($teacher)
            ->post(route('teacher.messages.reply', $message), ['body' => $reply])
            ->assertRedirect(route('teacher.messages.index', ['message' => $message->id]));

        $this->actingAs($guardian)
            ->get(route('guardian.messages.show', $message))
            ->assertOk()
            ->assertSee($reply);
        Notification::assertSentTo($guardian, MessageActivityNotification::class);
    }

    public function test_teacher_inbox_only_contains_threads_for_students_in_assigned_active_classes(): void
    {
        $teacher = $this->userWithRole('teacher');
        $otherTeacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $otherStudent = $this->userWithRole('student');
        $parent = $this->userWithRole('parent');

        $course = \App\Models\Course::query()->create([
            'title' => 'علوم', 'slug' => 'science-message-scope', 'status' => 'published',
            'type' => 'free', 'price' => 0,
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id, 'teacher_id' => $teacher->id,
            'name' => 'کلاس تحت مسئولیت', 'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $otherCourse = \App\Models\Course::query()->create([
            'title' => 'فیزیک', 'slug' => 'physics-message-scope', 'status' => 'published',
            'type' => 'free', 'price' => 0,
        ]);
        $otherClassroom = Classroom::query()->create([
            'course_id' => $otherCourse->id, 'teacher_id' => $otherTeacher->id,
            'name' => 'کلاس مدرس دیگر', 'status' => 'active',
        ]);
        $otherClassroom->students()->attach($otherStudent->id);

        Message::query()->create([
            'sender_id' => $parent->id, 'recipient_id' => $teacher->id, 'student_id' => $student->id,
            'subject' => 'پیام کلاس خود', 'body' => 'مجاز', 'status' => 'open',
        ]);
        Message::query()->create([
            'sender_id' => $parent->id, 'recipient_id' => $teacher->id, 'student_id' => $otherStudent->id,
            'subject' => 'پیام کلاس دیگر', 'body' => 'غیرمجاز', 'status' => 'open',
        ]);

        $this->actingAs($teacher)->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('پیام کلاس خود')
            ->assertDontSee('پیام کلاس دیگر');
    }

    public function test_teacher_cannot_send_a_message_to_a_guardian_not_linked_to_the_selected_student(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $unlinkedGuardian = $this->userWithRole('parent');
        $course = \App\Models\Course::query()->create([
            'title' => 'زیست', 'slug' => 'biology-message-link', 'status' => 'published',
            'type' => 'free', 'price' => 0,
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id, 'teacher_id' => $teacher->id,
            'name' => 'کلاس زیست', 'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $this->actingAs($teacher)->post(route('teacher.messages.store'), [
            'target' => "parent:{$classroom->id}:{$student->id}:{$unlinkedGuardian->id}",
            'subject' => 'پیام غیرمجاز',
            'body' => 'این پیام نباید ثبت شود.',
        ])->assertNotFound();

        $this->assertDatabaseMissing('messages', ['recipient_id' => $unlinkedGuardian->id]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));
        return $user;
    }
}
