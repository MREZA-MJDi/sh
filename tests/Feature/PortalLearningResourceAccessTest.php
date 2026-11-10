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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalLearningResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_student_can_download_only_resources_for_an_enrolled_class(): void
    {
        [$teacher, $course, $classroom, $resource] = $this->makePublishedResourceForStudent();

        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        Storage::disk('local')->put($resource->file_path, 'lesson material');

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertOk();

        $otherStudent = User::factory()->create();
        $otherStudent->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $this->actingAs($otherStudent)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound();
    }

    public function test_parent_can_download_a_child_resource_but_not_an_unlinked_child_resource(): void
    {
        [$teacher, $course, $classroom, $resource] = $this->makePublishedResourceForStudent();

        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $guardian = User::factory()->create();
        $guardian->roles()->attach(Role::query()->where('name', 'parent')->value('id'));
        $guardian->children()->attach($student->id);

        Storage::disk('local')->put($resource->file_path, 'lesson material');

        $this->actingAs($guardian)
            ->get(route('guardian.resources.download', ['resource' => $resource, 'child' => $student->id]))
            ->assertOk();

        $otherStudent = User::factory()->create();
        $otherStudent->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $this->get(route('guardian.resources.download', ['resource' => $resource, 'child' => $otherStudent->id]))
            ->assertNotFound();
    }

    private function makePublishedResourceForStudent(): array
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره منابع آموزشی',
            'slug' => 'resource-access-course-'.uniqid(),
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس منابع آموزشی',
            'status' => 'active',
        ]);

        $resource = LearningResource::query()->create([
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'uploaded_by' => $teacher->id,
            'title' => 'جزوه جلسه اول',
            'kind' => 'pdf',
            'is_free' => true,
            'price' => 0,
            'visibility' => 'enrolled',
            'file_path' => 'private/resources/lesson-one.pdf',
            'status' => 'published',
        ]);

        return [$teacher, $course, $classroom, $resource];
    }
}
