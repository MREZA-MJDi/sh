<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('جلسه دارای وضعیت ثبت‌شده');
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
