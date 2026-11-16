<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_can_update_name_and_phone_without_changing_login_email_or_grade(): void
    {
        $student = User::factory()->create([
            'email' => 'student-profile@example.invalid',
            'grade' => 'دهم',
        ]);
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $this->actingAs($student)
            ->get(route('student.profile.edit'))
            ->assertOk()
            ->assertSee('اطلاعات پروفایل')
            ->assertSee('student-profile@example.invalid');

        $this->actingAs($student)
            ->put(route('student.profile.update'), [
                'name' => 'دانش‌آموز به‌روزشده',
                'phone' => '۰۹۱۲۱۲۳۴۵۶۷',
            ])
            ->assertRedirect(route('student.profile.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'دانش‌آموز به‌روزشده',
            'phone' => '09121234567',
            'email' => 'student-profile@example.invalid',
            'grade' => 'دهم',
        ]);
    }

    public function test_student_profile_rejects_a_phone_number_owned_by_another_account(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        User::factory()->create(['phone' => '09120000000']);

        $this->actingAs($student)
            ->from(route('student.profile.edit'))
            ->put(route('student.profile.update'), [
                'name' => $student->name,
                'phone' => '۰۹۱۲۰۰۰۰۰۰۰',
            ])
            ->assertSessionHasErrors('phone');
    }
}
