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

    public function test_student_cannot_download_course_resources_even_when_enrolled(): void
    {
        [$teacher, $course, $classroom, $resource] = $this->makePublishedResourceForStudent();

        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        Storage::disk('local')->put($resource->file_path, 'lesson material');

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound();

        $otherStudent = User::factory()->create();
        $otherStudent->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $this->actingAs($otherStudent)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound();
    }

    public function test_parent_cannot_download_a_child_course_resource(): void
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
            ->assertNotFound();

        $otherStudent = User::factory()->create();
        $otherStudent->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $this->get(route('guardian.resources.download', ['resource' => $resource, 'child' => $otherStudent->id]))
            ->assertNotFound();
    }

    public function test_paid_resource_is_not_downloadable_without_a_verified_purchase(): void
    {
        [$teacher, $course, $classroom, $resource] = $this->makePublishedResourceForStudent();
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);
        $resource->update(['is_free' => false, 'price' => 25000]);

        Storage::disk('local')->put($resource->file_path, 'paid material');

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound();

        $this->assertDatabaseMissing('learning_resource_access_logs', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_resource_is_unavailable_before_its_release_time(): void
    {
        [, , $classroom, $resource] = $this->makePublishedResourceForStudent();
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);
        $resource->update(['release_at' => now()->addDay()]);

        Storage::disk('local')->put($resource->file_path, 'not released');

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound();
    }

    public function test_unscoped_authenticated_resource_is_not_downloadable(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $resource = LearningResource::query()->create([
            'uploaded_by' => $student->id,
            'title' => 'راهنمای عمومی ورود',
            'kind' => 'pdf',
            'is_free' => true,
            'price' => 0,
            'visibility' => 'authenticated',
            'file_path' => 'private/resources/general-guide.pdf',
            'status' => 'published',
        ]);
        Storage::disk('local')->put($resource->file_path, 'general guide');

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertOk();

        $this->assertDatabaseMissing('learning_resource_access_logs', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_view_only_pdf_is_not_streamed_inline_to_parent(): void
    {
        [, , $classroom, $resource] = $this->makePublishedResourceForStudent();
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $guardian = User::factory()->create();
        $guardian->roles()->attach(Role::query()->where('name', 'parent')->value('id'));
        $guardian->children()->attach($student->id);
        $resource->update(['downloadable' => false]);
        Storage::disk('local')->put($resource->file_path, '%PDF-1.4 sample');

        $response = $this->actingAs($guardian)
            ->get(route('guardian.resources.download', ['resource' => $resource, 'child' => $student->id]))
            ->assertOk();

        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('learning_resource_access_logs', [
            'learning_resource_id' => $resource->id,
            'user_id' => $guardian->id,
            'student_id' => $student->id,
            'event_type' => 'view',
        ]);
    }

    public function test_public_unscoped_free_resource_is_not_downloadable_to_guests(): void
    {
        $resource = LearningResource::query()->create([
            'title' => 'فایل عمومی',
            'kind' => 'pdf',
            'is_free' => true,
            'price' => 0,
            'visibility' => 'public',
            'file_path' => 'private/resources/public-guide.pdf',
            'status' => 'published',
        ]);
        Storage::disk('local')->put($resource->file_path, '%PDF-1.4 public');

        $this->get(route('public.resources.download', $resource))->assertOk();

        $this->assertDatabaseMissing('learning_resource_access_logs', [
            'learning_resource_id' => $resource->id,
            'user_id' => null,
        ]);
    }

    public function test_public_visibility_does_not_bypass_course_or_class_membership(): void
    {
        [, , , $resource] = $this->makePublishedResourceForStudent();
        $resource->update(['visibility' => 'public']);
        Storage::disk('local')->put($resource->file_path, 'still scoped');

        $this->get(route('public.resources.download', $resource))->assertNotFound();
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
