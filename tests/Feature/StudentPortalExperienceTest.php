<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Message;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class StudentPortalExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_with_own_attendance_permission_can_open_personal_attendance(): void
    {
        $student = $this->userWithRole('student');

        $this->actingAs($student)
            ->get(route('student.attendance'))
            ->assertOk()
            ->assertSee('حضور و غیاب')
            ->assertSee('سابقه‌ای برای این ماه ثبت نشده است.');
    }

    public function test_student_attendance_shows_saved_records_for_their_active_class_only(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24)->setTime(12, 0));
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');

        foreach (['late', 'absent'] as $status) {
            $classroom = Classroom::query()->create([
                'teacher_id' => $teacher->id,
                'name' => 'کلاس '.$status,
                'status' => 'active',
            ]);
            if ($status === 'late') {
                $classroom->students()->attach($student);
            }
            $session = ClassSession::query()->create([
                'classroom_id' => $classroom->id,
                'title' => 'جلسه حضور و غیاب',
                'session_type' => 'onsite',
                'starts_at' => now()->subHours(2),
                'ends_at' => now()->subHour(),
                'status' => 'completed',
                'created_by' => $teacher->id,
            ]);
            Attendance::query()->create([
                'classroom_id' => $classroom->id,
                'class_session_id' => $session->id,
                'student_id' => $student->id,
                'session_date' => now()->toDateString(),
                'status' => $status,
                'marked_by' => $teacher->id,
            ]);
        }

        $this->actingAs($student)->get(route('student.attendance'))
            ->assertOk()
            ->assertSee('1 جلسه ثبت‌شده')
            ->assertSee('تأخیر')
            ->assertDontSee('کلاس absent');
        $this->get(route('student.attendance', ['month' => '1405-08']))
            ->assertOk()->assertSee('سابقه‌ای برای این ماه ثبت نشده است.');
    }

    public function test_student_without_own_attendance_permission_does_not_see_or_access_attendance(): void
    {
        $student = $this->userWithRole('student', ['permissions_customized' => true]);
        $student->permissions()->sync(
            Permission::query()->where('name', 'dashboard.view')->pluck('id')
        );

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee(route('student.attendance'));

        $this->actingAs($student)
            ->get(route('student.attendance'))
            ->assertForbidden();
    }

    public function test_student_new_message_form_has_a_structured_recipient_subject_and_body_flow(): void
    {
        $student = $this->userWithRole('student');
        $admin = $this->userWithRole('admin');

        $this->actingAs($student)
            ->get(route('student.messages.create'))
            ->assertOk()
            ->assertSee('ارسال پیام جدید')
            ->assertSee('name="recipient_id"', false)
            ->assertSee('value="'.$admin->id.'"', false)
            ->assertSee('name="subject"', false)
            ->assertSee('name="body"', false)
            ->assertSee('بازگشت به پیام‌ها');
    }

    public function test_student_message_composer_explains_when_no_recipient_is_available(): void
    {
        $student = $this->userWithRole('student');

        $this->actingAs($student)
            ->get(route('student.messages.create'))
            ->assertOk()
            ->assertSee('در حال حاضر گیرنده‌ای برای ارسال پیام در دسترس نیست.')
            ->assertDontSee('name="recipient_id"', false);
    }

    public function test_student_can_send_a_message_to_an_available_school_admin(): void
    {
        Notification::fake();

        $student = $this->userWithRole('student');
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($student)->post(route('student.messages.store'), [
            'student_id' => $student->id,
            'recipient_id' => $admin->id,
            'subject' => 'پرسش درباره تکلیف',
            'body' => 'لطفاً درباره مهلت ارسال تکلیف راهنمایی کنید.',
        ]);

        $message = Message::query()->where('sender_id', $student->id)->sole();

        $response->assertRedirect(route('student.messages.show', $message))
            ->assertSessionHas('status', 'پیام شما با موفقیت ارسال شد.');

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender_id' => $student->id,
            'recipient_id' => $admin->id,
            'student_id' => $student->id,
            'parent_id' => null,
            'subject' => 'پرسش درباره تکلیف',
            'status' => 'open',
        ]);

        Notification::assertSentTo($admin, \App\Notifications\MessageActivityNotification::class);
    }

    public function test_student_cannot_send_a_message_to_an_unrelated_teacher(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');

        $this->actingAs($student)
            ->post(route('student.messages.store'), [
                'student_id' => $student->id,
                'recipient_id' => $teacher->id,
                'subject' => 'پرسش درباره تکلیف',
                'body' => 'لطفاً درباره مهلت ارسال تکلیف راهنمایی کنید.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('messages', [
            'sender_id' => $student->id,
            'recipient_id' => $teacher->id,
            'subject' => 'پرسش درباره تکلیف',
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
