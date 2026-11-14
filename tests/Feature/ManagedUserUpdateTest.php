<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManagedUserUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_can_update_student_profile_and_class_memberships(): void
    {
        $admin = $this->admin();
        $student = $this->userWithRole('student');
        $classroom = Classroom::query()->create(['teacher_id' => $admin->id, 'name' => 'کلاس جدید', 'status' => 'active']);

        $this->actingAs($admin)->patch(route('admin.students.update', $student), [
            'name' => 'دانش‌آموز اصلاح‌شده',
            'email' => $student->email,
            'phone' => '09120000000',
            'external_student_id' => 'QZ-102',
            'grade' => 'دوازدهم',
            'classroom_ids' => [$classroom->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'name' => 'دانش‌آموز اصلاح‌شده', 'grade' => 'دوازدهم']);
        $this->assertDatabaseHas('classroom_student', ['classroom_id' => $classroom->id, 'student_id' => $student->id]);
    }

    public function test_admin_can_update_parent_profile_and_child_links(): void
    {
        $admin = $this->admin();
        $parent = $this->userWithRole('parent');
        $child = $this->userWithRole('student');

        $this->actingAs($admin)->patch(route('admin.parents.update', $parent), [
            'name' => 'ولی اصلاح‌شده',
            'email' => $parent->email,
            'phone' => '09121111111',
            'child_ids' => [$child->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $parent->id, 'name' => 'ولی اصلاح‌شده']);
        $this->assertTrue($parent->fresh()->children()->whereKey($child->id)->exists());
    }

    private function admin(): User
    {
        return $this->userWithRole('admin');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());
        return $user;
    }
}
