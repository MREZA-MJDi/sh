<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Course;
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
            ->get(route('teacher.classes', ['sort' => 'students']))
            ->assertOk()
            ->assertSee('کلاس شماره 1')
            ->assertDontSee('کلاس ویژه جستجو');

        $this->actingAs($teacher)
            ->get(route('teacher.classes', ['sort' => 'students', 'page' => 2]))
            ->assertOk()
            ->assertSee('کلاس ویژه جستجو');

        $this->actingAs($teacher)
            ->get(route('teacher.classes', ['q' => 'ویژه جستجو']))
            ->assertOk()
            ->assertSee('کلاس ویژه جستجو')
            ->assertDontSee('کلاس شماره 1');
    }

    public function test_teacher_attendance_roster_is_paginated_for_large_classes(): void
    {
        $teacher = $this->teacher();
        $teacher->permissions()->sync(Permission::query()
            ->whereIn('name', [
                'dashboard.view',
                'classes.view.assigned',
                'attendance.view.assigned',
                'attendance.manage.assigned',
            ])
            ->pluck('id'));

        $course = Course::query()->create([
            'title' => 'دوره حضور و غیاب',
            'slug' => 'teacher-attendance-roster-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس بزرگ مدرس',
            'status' => 'active',
        ]);
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه حضور و غیاب',
            'session_type' => 'onsite',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->subMinutes(10),
            'status' => 'scheduled',
        ]);

        for ($index = 1; $index <= 101; $index++) {
            $student = User::factory()->create([
                'name' => 'دانش‌آموز '.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            ]);
            $classroom->students()->attach($student->id);
        }

        $query = [
            'classroom_id' => $classroom->id,
            'class_session_id' => $session->id,
            'date' => $session->starts_at->toDateString(),
        ];

        $this->actingAs($teacher)
            ->get(route('teacher.attendance', $query))
            ->assertOk()
            ->assertSee('دانش‌آموز 001')
            ->assertDontSee('دانش‌آموز 101');

        $this->actingAs($teacher)
            ->get(route('teacher.attendance', [...$query, 'students' => 2]))
            ->assertOk()
            ->assertSee('دانش‌آموز 101');
    }

    public function test_teacher_list_filters_reject_unsupported_sort_values(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)
            ->get(route('teacher.classes', ['sort' => 'untrusted-column']))
            ->assertSessionHasErrors('sort');
    }
}
