<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\LearningResource;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ResourceDashboardScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_and_parent_dashboards_do_not_list_a_resource_with_mismatched_course_and_classroom(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $parent = $this->userWithRole('parent');
        $parent->children()->attach($student->id);

        $courseA = $this->courseFor($teacher, 'dashboard-resource-course-a');
        $courseB = $this->courseFor($teacher, 'dashboard-resource-course-b');

        $classA = $this->classroomFor($teacher, $courseA, 'کلاس دوره الف');
        $classB = $this->classroomFor($teacher, $courseB, 'کلاس دوره ب');
        $classA->students()->attach($student->id);
        $classB->students()->attach($student->id);

        $enrollments = app(CourseEnrollmentService::class);
        $enrollments->syncClassroomRoster($classA);
        $enrollments->syncClassroomRoster($classB);

        LearningResource::query()->create([
            'course_id' => $courseA->id,
            'classroom_id' => $classB->id,
            'uploaded_by' => $teacher->id,
            'title' => 'جزوه ناسازگار نباید نمایش داده شود',
            'kind' => 'pdf',
            'price' => 0,
            'is_free' => true,
            'visibility' => 'enrolled',
            'status' => 'published',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('جزوه ناسازگار نباید نمایش داده شود');

        $this->actingAs($parent)
            ->get(route('guardian.dashboard'))
            ->assertOk()
            ->assertDontSee('جزوه ناسازگار نباید نمایش داده شود');
    }

    public function test_parent_resource_page_is_paginated_for_large_resource_lists(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $parent = $this->userWithRole('parent');
        $parent->children()->attach($student->id);

        $course = $this->courseFor($teacher, 'parent-resource-pagination');
        $classroom = $this->classroomFor($teacher, $course, 'کلاس منابع');
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        for ($index = 1; $index <= 13; $index++) {
            LearningResource::query()->create([
                'course_id' => $course->id,
                'classroom_id' => $classroom->id,
                'uploaded_by' => $teacher->id,
                'title' => 'منبع والدین '.$index,
                'kind' => 'pdf',
                'price' => 0,
                'is_free' => true,
                'visibility' => 'enrolled',
                'status' => 'published',
            ]);
        }

        $this->actingAs($parent)
            ->get(route('guardian.resources.index'))
            ->assertOk()
            ->assertSee('منبع والدین 1')
            ->assertDontSee('منبع والدین 13');

        $this->actingAs($parent)
            ->get(route('guardian.resources.index', ['resources' => 2]))
            ->assertOk()
            ->assertSee('منبع والدین 13');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }

    private function courseFor(User $teacher, string $slug): Course
    {
        return Course::query()->create([
            'title' => 'دوره '.$slug,
            'slug' => $slug,
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
    }

    private function classroomFor(User $teacher, Course $course, string $name): Classroom
    {
        return Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }
}
