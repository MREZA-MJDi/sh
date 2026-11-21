<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AdminCourseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_course_editor_uses_public_page_sections_and_private_media_uploads(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.courses.create'))
            ->assertOk()
            ->assertSee('سربرگ صفحه عمومی دوره')
            ->assertSee('ثبت‌نام و قیمت')
            ->assertSee('امکاناتی که دانش‌آموز در صفحه دوره می‌بیند');

        $course = $this->course();
        $course->update(['learning_features' => ['recorded_video' => true]]);

        $this->actingAs($admin)
            ->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSee('ویدیوی معرفی / تریلر دوره')
            ->assertSee('ویدیوهای آموزشی دوره')
            ->assertSee('مدیریت فصل‌ها و متن درس‌ها')
            ->assertSee(route('public.courses.show', ['slug' => $course->slug, 'preview' => 1]));
    }


    public function test_admin_course_cover_upload_is_stored_and_rendered_on_public_course_pages(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'دوره دارای تصویر',
                'description' => 'معرفی دوره',
                'subject' => 'ریاضی',
                'grade' => 'دهم',
                'teacher_id' => null,
                'type' => 'free',
                'price' => 0,
                'delivery_type' => 'onsite',
                'status' => 'published',
                'cover_image' => UploadedFile::fake()->image('cover.jpg', 1600, 900),
            ])
            ->assertRedirect();

        $course = Course::query()->where('title', 'دوره دارای تصویر')->firstOrFail();

        $this->assertNotEmpty($course->cover_image_path);
        Storage::disk('public')->assertExists($course->cover_image_path);

        $this->get(route('public.courses.index'))
            ->assertOk()
            ->assertSee($course->coverImageUrl, false);

        $this->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee($course->coverImageUrl, false);
    }

    public function test_admin_can_create_update_and_delete_an_empty_course(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'دوره مدیریت‌شده',
                'description' => 'توضیحات دوره',
                'subject' => 'ریاضی',
                'grade' => 'دهم',
                'teacher_id' => null,
                'type' => 'paid',
                'price' => 850000,
                'delivery_type' => 'onsite',
                'status' => 'draft',
            ])
            ->assertRedirect();

        $course = Course::query()->where('title', 'دوره مدیریت‌شده')->firstOrFail();

        $this->assertSame('paid', $course->type);
        $this->assertSame(850000, (int) $course->price);
        $this->assertSame('draft', $course->status);
        $this->assertNotSame('', $course->slug);

        $this->actingAs($admin)
            ->patch(route('admin.courses.update', $course), [
                'title' => 'دوره به‌روزرسانی‌شده',
                'description' => 'توضیحات جدید',
                'subject' => 'فیزیک',
                'grade' => 'یازدهم',
                'teacher_id' => null,
                'type' => 'free',
                'price' => 0,
                'delivery_type' => 'onsite',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'دوره به‌روزرسانی‌شده',
            'type' => 'free',
            'price' => 0,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect();

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_admin_can_create_update_and_remove_sections_and_lessons(): void
    {
        $admin = $this->userWithRole('admin');
        $course = $this->course();

        $this->actingAs($admin)
            ->post(route('admin.courses.sections.store', $course), [
                'title' => 'بخش اولیه',
                'description' => 'توضیح اولیه',
                'sort_order' => 1,
                'status' => 'draft',
            ])
            ->assertRedirect();

        $section = CourseSection::query()->where('course_id', $course->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.courses.sections.update', $section), [
                'title' => 'بخش منتشرشده',
                'description' => 'توضیح به‌روزشده',
                'sort_order' => 2,
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.courses.lessons.store', $section), [
                'title' => 'درس اولیه',
                'body' => 'محتوای درس',
                'duration_minutes' => 35,
                'sort_order' => 1,
                'status' => 'draft',
            ])
            ->assertRedirect();

        $lesson = Lesson::query()->where('course_section_id', $section->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.courses.lessons.update', $lesson), [
                'title' => 'درس منتشرشده',
                'body' => 'محتوای اصلاح‌شده',
                'duration_minutes' => 40,
                'sort_order' => 2,
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'درس منتشرشده',
            'duration_minutes' => 40,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.lessons.destroy', $lesson))
            ->assertRedirect();

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.sections.destroy', $section))
            ->assertRedirect();

        $this->assertDatabaseMissing('course_sections', ['id' => $section->id]);
    }

    public function test_course_with_existing_learning_content_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $course = $this->course();
        $course->sections()->create([
            'title' => 'سرفصل دارای سابقه',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.courses.index'))
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'))
            ->assertSessionHasErrors('course');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }

    private function course(): Course
    {
        return Course::query()->create([
            'title' => 'دوره آزمایشی CRUD',
            'slug' => 'admin-course-workflow-'.uniqid(),
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'draft',
        ]);
    }
}
