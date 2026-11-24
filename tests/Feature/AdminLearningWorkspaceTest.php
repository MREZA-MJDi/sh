<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminLearningWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_find_a_course_and_open_its_class_and_enrollment_workflows(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->sole());
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->sole());
        $course = Course::query()->create([
            'title' => 'LMS sample course',
            'slug' => 'lms-sample-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'LMS sample class',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('admin.lms.index', ['q' => 'sample', 'status' => 'published']))
            ->assertOk()
            ->assertSee(route('admin.lms.show', $course), false);

        $this->get(route('admin.lms.show', $course))
            ->assertOk()
            ->assertSee(route('admin.classes.sessions.index', $classroom), false)
            ->assertSee(route('admin.enrollments.index', ['course_id' => $course->id]), false)
            ->assertSee(route('admin.homework.index', ['classroom_id' => $classroom->id]), false)
            ->assertSee(route('admin.exams.index', ['course_id' => $course->id]), false);

        $this->actingAs($teacher)->get(route('admin.lms.show', $course))->assertForbidden();
    }
}
