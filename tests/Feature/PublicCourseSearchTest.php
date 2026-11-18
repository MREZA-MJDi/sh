<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicCourseSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_course_search_matches_active_teacher_and_published_lesson_but_not_draft_content(): void
    {
        $teacher = User::factory()->create([
            'name' => 'آرش مدرس',
            'is_active' => true,
        ]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->firstOrFail());

        $course = Course::query()->create([
            'title' => 'دوره جستجوی مدرس',
            'slug' => 'search-by-teacher-and-lesson',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $section = $course->sections()->create([
            'title' => 'فصل هندسه',
            'status' => 'published',
        ]);

        $section->lessons()->create([
            'title' => 'هندسه تحلیلی پیشرفته',
            'status' => 'published',
        ]);

        $section->lessons()->create([
            'title' => 'عبارت محرمانه پیش‌نویس',
            'status' => 'draft',
        ]);

        $inactiveTeacher = User::factory()->create([
            'name' => 'مدرس غیرفعال ویژه',
            'is_active' => false,
        ]);
        $inactiveTeacher->roles()->attach(Role::query()->where('name', 'teacher')->firstOrFail());

        Course::query()->create([
            'title' => 'دوره مدرس غیرفعال',
            'slug' => 'inactive-teacher-course',
            'teacher_id' => $inactiveTeacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $this->get(route('public.courses.index', ['q' => 'آرش مدرس']))
            ->assertOk()
            ->assertSee('دوره جستجوی مدرس');

        $this->get(route('public.courses.index', ['q' => 'هندسه تحلیلی']))
            ->assertOk()
            ->assertSee('دوره جستجوی مدرس');

        $this->get(route('public.courses.index', ['q' => 'عبارت محرمانه پیش‌نویس']))
            ->assertOk()
            ->assertDontSee('دوره جستجوی مدرس');

        $this->get(route('public.courses.index', ['q' => 'مدرس غیرفعال ویژه']))
            ->assertOk()
            ->assertDontSee('دوره مدرس غیرفعال');
    }
    public function test_public_course_subject_filters_and_teacher_navigation_render_successfully(): void
    {
        $teacher = User::factory()->create([
            'name' => 'مدرس نمونه',
            'subject' => 'زیست',
            'is_active' => true,
        ]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->firstOrFail());

        foreach (['شیمی', 'زیست'] as $subject) {
            Course::query()->create([
                'title' => 'دوره '.$subject,
                'slug' => 'public-'.$subject.'-'.uniqid(),
                'teacher_id' => $teacher->id,
                'subject' => $subject,
                'type' => 'free',
                'price' => 0,
                'delivery_type' => 'onsite',
                'status' => 'published',
            ]);
        }

        $this->get(route('public.courses.index', ['subject' => 'شیمی']))
            ->assertOk()
            ->assertSee('دوره شیمی');

        $this->get(route('public.courses.index', ['subject' => 'زیست']))
            ->assertOk()
            ->assertSee('دوره زیست');

        $this->get(route('public.teachers.index'))
            ->assertOk()
            ->assertSee('مدرس نمونه');

        $this->get(route('public.teachers.show', $teacher))
            ->assertOk()
            ->assertSee('دوره زیست');
    }

}
