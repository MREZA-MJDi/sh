<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Exam;
use App\Models\LearningResource;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EducationalRecordDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_class_with_exam_cannot_be_deleted_and_exam_scope_is_preserved(): void
    {
        $admin = $this->userWithRole('admin');
        $teacher = $this->userWithRole('teacher');
        $course = $this->courseFor($teacher, 'class-delete-protection');
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس دارای آزمون',
            'status' => 'active',
        ]);
        $exam = Exam::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'آزمون محفوظ',
            'provider' => 'internal',
            'status' => 'published',
            'duration_minutes' => 60,
            'attempts_allowed' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.classes.destroy', $classroom))
            ->assertSessionHasErrors('classroom');

        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id]);
        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'classroom_id' => $classroom->id,
        ]);
    }

    public function test_course_with_curriculum_or_resources_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $teacher = $this->userWithRole('teacher');
        $course = $this->courseFor($teacher, 'course-delete-protection');

        CourseSection::query()->create([
            'course_id' => $course->id,
            'title' => 'بخش دارای محتوای آموزشی',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        LearningResource::query()->create([
            'course_id' => $course->id,
            'uploaded_by' => $teacher->id,
            'title' => 'جزوه دوره محفوظ',
            'kind' => 'pdf',
            'price' => 0,
            'is_free' => true,
            'visibility' => 'enrolled',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.destroy', $course))
            ->assertSessionHasErrors('course');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('course_sections', ['course_id' => $course->id]);
        $this->assertDatabaseHas('learning_resources', ['course_id' => $course->id]);
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
}
