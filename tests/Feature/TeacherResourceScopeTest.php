<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\LearningResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TeacherResourceScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_teacher_assigned_only_through_a_classroom_can_create_and_list_its_resource(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره تخصیص کلاسی',
            'slug' => 'class-only-resource-course',
            'teacher_id' => null,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس من',
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->post(route('admin.resources.store'), [
                'title' => 'جزوه کلاس من',
                'course_id' => $course->id,
                'classroom_id' => $classroom->id,
                'kind' => 'pdf',
                'price' => 0,
                'is_free' => 1,
                'visibility' => 'enrolled',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_resources', [
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'title' => 'جزوه کلاس من',
            'uploaded_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.resources.index'))
            ->assertOk()
            ->assertSee('جزوه کلاس من');
    }

    public function test_teacher_cannot_list_download_or_create_a_resource_with_mismatched_course_and_classroom(): void
    {
        $teacher = User::factory()->create(['permissions_customized' => true]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));
        $teacher->permissions()->sync(
            Permission::query()->whereIn('name', ['dashboard.view', 'resources.manage.assigned'])->pluck('id')
        );

        $ownCourse = Course::query()->create([
            'title' => 'دوره تخصیص‌یافته',
            'slug' => 'resource-own-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $otherCourse = Course::query()->create([
            'title' => 'دوره متعلق به مدرس دیگر',
            'slug' => 'resource-other-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $otherCourse->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس مستقل مدرس',
            'status' => 'active',
        ]);

        Storage::disk('local')->put('private/resources/mismatched.pdf', 'private');
        $resource = LearningResource::query()->create([
            'course_id' => $ownCourse->id,
            'classroom_id' => $classroom->id,
            'uploaded_by' => $teacher->id,
            'title' => 'جزوه با محدوده ناسازگار',
            'kind' => 'pdf',
            'price' => 0,
            'is_free' => true,
            'visibility' => 'enrolled',
            'file_path' => 'private/resources/mismatched.pdf',
            'status' => 'published',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.resources.index'))
            ->assertOk()
            ->assertDontSee('جزوه با محدوده ناسازگار');

        $this->actingAs($teacher)
            ->get(route('admin.resources.download', $resource))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->post(route('admin.resources.store'), [
                'title' => 'جزوه جدید ناسازگار',
                'kind' => 'pdf',
                'course_id' => $ownCourse->id,
                'classroom_id' => $classroom->id,
                'is_free' => 1,
                'price' => 0,
                'visibility' => 'enrolled',
                'status' => 'published',
            ])
            ->assertSessionHasErrors('classroom_id');

        $this->assertDatabaseMissing('learning_resources', [
            'title' => 'جزوه جدید ناسازگار',
        ]);
    }
}
