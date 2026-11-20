<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherPanelListingExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    private function teacher(): User
    {
        $teacher = User::factory()->create(['permissions_customized' => true]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));
        $teacher->permissions()->sync(Permission::query()
            ->whereIn('name', ['dashboard.view', 'classes.view.assigned'])
            ->pluck('id'));

        return $teacher;
    }

    public function test_teacher_class_list_is_paginated_and_search_is_scoped_to_assigned_classes(): void
    {
        $teacher = $this->teacher();
        $course = Course::query()->create([
            'title' => 'دوره کلاس‌های مدرس',
            'slug' => 'teacher-listing-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        for ($index = 1; $index <= 13; $index++) {
            Classroom::query()->create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'name' => $index === 13 ? 'کلاس ویژه جستجو' : 'کلاس شماره '.$index,
                'status' => 'active',
            ]);
        }

        $this->actingAs($teacher)
            ->get(route('teacher.classes'))
            ->assertOk()
            ->assertSee('کلاس شماره 1')
            ->assertDontSee('کلاس ویژه جستجو');

        $this->actingAs($teacher)
            ->get(route('teacher.classes', ['page' => 2]))
            ->assertOk()
            ->assertSee('کلاس ویژه جستجو');

        $this->actingAs($teacher)
            ->get(route('teacher.classes', ['q' => 'ویژه جستجو']))
            ->assertOk()
            ->assertSee('کلاس ویژه جستجو')
            ->assertDontSee('کلاس شماره 1');
    }

    public function test_teacher_list_filters_reject_unsupported_sort_values(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)
            ->get(route('teacher.classes', ['sort' => 'untrusted-column']))
            ->assertSessionHasErrors('sort');
    }
}
