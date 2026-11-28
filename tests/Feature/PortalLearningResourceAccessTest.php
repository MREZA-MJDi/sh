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

    public function test_student_can_view_only_resources_for_an_enrolled_class(): void
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

    public function test_parent_can_view_a_child_resource_but_not_an_unlinked_child_resource(): void
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

    public function test_student_can_view_free_course_material_without_a_course_enrollment(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره رایگان بدون ثبت‌نام دستی',
            'slug' => 'free-course-resource-access-'.uniqid(),
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
            'learning_features' => ['resources' => true],
        ]);

        $resource = LearningResource::query()->create([
            'course_id' => $course->id,
            'uploaded_by' => $teacher->id,
            'title' => 'جزوه رایگان دوره',
            'kind' => 'pdf',
            'is_free' => true,
            'price' => 0,
            'visibility' => 'enrolled',
            'file_path' => 'private/resources/free-course-without-enrollment.pdf',
            'status' => 'published',
        ]);

        Storage::disk('local')->put($resource->file_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4XcAAAAASUVORK5CYII='));

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertOk();

        $this->assertDatabaseMissing('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        $this->assertDatabaseHas('learning_resource_access_logs', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
            'student_id' => $student->id,
            'event_type' => 'view',
        ]);
    }

    public function test_paid_resource_can_only_be_viewed_after_a_verified_purchase(): void
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

        $viewer = route('student.resources.download', $resource);
        $this->post(route('student.resources.purchase', $resource), [
            'provider_reference' => 'receipt-25000',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('resource_purchases', [
            'learning_resource_id' => $resource->id,
            'student_id' => $student->id,
            'provider_reference' => 'receipt-25000',
            'status' => 'pending_review',
        ]);
        $this->get($viewer)->assertNotFound();
        $this->get($viewer.'?render=1')->assertNotFound();

        \App\Models\ResourcePurchase::query()
            ->where('learning_resource_id', $resource->id)
            ->where('student_id', $student->id)
            ->update(['status' => 'paid']);

        $this->get($viewer)
            ->assertOk()
            ->assertViewIs('learning.document-reader')
            ->assertHeaderMissing('Content-Disposition');
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

    public function test_unscoped_authenticated_resource_can_be_accessed_and_access_is_logged(): void
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

        $this->assertDatabaseHas('learning_resource_access_logs', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
            'student_id' => $student->id,
            'event_type' => 'view',
        ]);
    }

    public function test_only_page_images_are_served_and_parent_access_is_logged_for_selected_child(): void
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
        Storage::disk('local')->put($resource->file_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4XcAAAAASUVORK5CYII='));

        $response = $this->actingAs($guardian)
            ->get(route('guardian.resources.download', ['resource' => $resource, 'child' => $student->id]))
            ->assertOk();

        $response->assertViewIs('learning.document-reader')->assertHeaderMissing('Content-Disposition');
        $this->assertDatabaseHas('learning_resource_access_logs', [
            'learning_resource_id' => $resource->id,
            'user_id' => $guardian->id,
            'student_id' => $student->id,
            'event_type' => 'view',
        ]);
    }

    public function test_public_unscoped_free_resource_requires_login_and_logs_the_viewer(): void
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
        Storage::disk('local')->put($resource->file_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4XcAAAAASUVORK5CYII='));

        $url = route('public.resources.download', $resource);
        $this->get($url)->assertRedirect(route('login'));
        $this->get($url.'?render=1')->assertRedirect(route('login'));
        $viewer = User::factory()->create(['is_active' => true]);
        $this->actingAs($viewer)->get($url)->assertOk()->assertSee('آکادمی شیخان');

        $this->assertDatabaseHas('learning_resource_access_logs', [
            'learning_resource_id' => $resource->id,
            'user_id' => $viewer->id,
            'student_id' => null,
            'event_type' => 'view',
        ]);
    }

    public function test_public_course_resource_appears_on_course_page_and_can_be_viewed(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره با جزوه عمومی',
            'slug' => 'public-resource-course-'.uniqid(),
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
            'learning_features' => ['resources' => true],
        ]);

        $resource = LearningResource::query()->create([
            'course_id' => $course->id,
            'uploaded_by' => $teacher->id,
            'title' => 'جزوه رایگان فصل اول',
            'kind' => 'pdf',
            'is_free' => true,
            'price' => 0,
            'visibility' => 'public',
            'file_path' => 'private/resources/public-course-guide.pdf',
            'status' => 'published',
        ]);
        Storage::disk('local')->put($resource->file_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4XcAAAAASUVORK5CYII='));

        $this->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('جزوه‌ها و فایل‌های این دوره')
            ->assertSee('ورود برای مشاهده')
            ->assertSee('جزوه رایگان فصل اول');

        $student = User::factory()->create(['is_active' => true]);
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $viewerUrl = route('public.resources.download', $resource);

        $this->actingAs($student)->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee($viewerUrl)
            ->assertDontSee(route('student.resources.download', $resource));
        $this->get($viewerUrl)->assertOk()->assertViewIs('learning.document-reader');
    }

    public function test_public_visibility_does_not_bypass_course_or_class_membership(): void
    {
        [, , , $resource] = $this->makePublishedResourceForStudent();
        $resource->update(['visibility' => 'public']);
        Storage::disk('local')->put($resource->file_path, 'still scoped');

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('public.resources.download', $resource))->assertNotFound();
    }

    public function test_public_pdf_returns_a_page_image_instead_of_the_original_document(): void
    {
        foreach (['pdfinfo', 'pdftoppm'] as $program) {
            if (! (new \Symfony\Component\Process\ExecutableFinder)->find($program)) {
                $this->markTestSkipped('Poppler binaries are required for this test.');
            }
        }

        $resource = LearningResource::query()->create([
            'title' => 'PDF reader fixture',
            'kind' => 'pdf',
            'is_free' => true,
            'price' => 0,
            'visibility' => 'public',
            'file_path' => 'testing/document.pdf',
            'status' => 'published',
        ]);
        $source = file_get_contents(base_path('docs/academy-client-handoff-fa.pdf'));
        Storage::disk('local')->put($resource->file_path, $source);

        $this->actingAs(User::factory()->create(['is_active' => true, 'phone' => '09121234567']));
        $this->get(route('public.resources.download', $resource))
            ->assertOk()->assertViewIs('learning.document-reader');

        $page = $this->get(route('public.resources.download', ['resource' => $resource, 'page' => 2, 'render' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeaderMissing('Content-Disposition');

        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $page->getContent());
        $anotherPage = $this->actingAs(User::factory()->create(['is_active' => true, 'phone' => '09127654321']))
            ->get(route('public.resources.download', ['resource' => $resource, 'page' => 2, 'render' => 1]))
            ->assertOk();
        $this->assertNotSame($page->getContent(), $anotherPage->getContent());
        $resource->update(['visibility' => 'enrolled']);
        $this->get(route('public.resources.download', ['resource' => $resource, 'page' => 2, 'render' => 1]))
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
