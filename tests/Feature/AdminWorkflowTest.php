<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_can_create_student_account_with_role(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'دانش‌آموز آزمایشی',
            'email' => 'student@example.test',
            'phone' => '09120000000',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'grade' => 'دوازدهم',
        ])->assertRedirect();

        $student = User::where('email', 'student@example.test')->firstOrFail();

        $this->assertTrue($student->hasRole('student'));
        $this->assertSame('دوازدهم', $student->grade);
    }

    public function test_admin_can_create_published_course(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $this->actingAs($admin)->post(route('admin.courses.store'), [
            'title' => 'دوره ریاضی دوازدهم',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'status' => 'published',
        ])->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'title' => 'دوره ریاضی دوازدهم',
            'type' => 'free',
            'status' => 'published',
            'price' => 0,
        ]);
    }
}
