<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherSharedAdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    private function teacherWithPermissions(array $names): User
    {
        $teacher = User::factory()->create(['permissions_customized' => true]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));
        $teacher->permissions()->sync(
            Permission::query()->whereIn('name', array_unique(['dashboard.view', ...$names]))->pluck('id')
        );

        return $teacher;
    }

    private function courseFor(User $teacher, string $slug): Course
    {
        return Course::query()->create([
            'title' => 'دوره '.$slug,
            'slug' => $slug,
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'skyroom',
            'status' => 'published',
        ]);
    }

    public function test_teacher_opens_the_same_admin_shell_and_only_selected_menu_items(): void
    {
        $teacher = $this->teacherWithPermissions([
            'classes.view.assigned',
            'classes.manage.assigned',
            'courses.view.assigned',
            'students.view.assigned',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('کلاس‌ها و اسکای‌روم')
            ->assertDontSee('کوییز ۲۴')
            ->assertDontSee('آزمون‌ها')
            ->assertDontSee('مدیریت کاربران');

        $this->actingAs($teacher)
            ->get(route('admin.exams.index'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.teachers.index'))
            ->assertForbidden();
    }

    public function test_teacher_can_create_a_skyroom_class_only_for_an_assigned_course(): void
    {
        $teacher = $this->teacherWithPermissions(['classes.manage.assigned']);
        $ownCourse = $this->courseFor($teacher, 'teacher-own-skyroom-course');

        $this->actingAs($teacher)
            ->post(route('admin.classes.store'), [
                'name' => 'کلاس آنلاین مدرس',
                'course_id' => $ownCourse->id,
                'meeting_url' => 'https://www.skyroom.online/ch/academy/teacher-class',
                'schedule_days' => ['sat', 'mon'],
                'schedule_time' => '16:30',
                'duration_minutes' => 90,
                'status' => 'active',
            ])
            ->assertSessionHasNoErrors();

        $classroom = Classroom::query()->where('name', 'کلاس آنلاین مدرس')->firstOrFail();

        $this->assertSame($teacher->id, $classroom->teacher_id);
        $this->assertSame($ownCourse->id, $classroom->course_id);
        $this->assertSame('https://www.skyroom.online/ch/academy/teacher-class', $classroom->meeting_url);
        $this->assertSame(90, $classroom->schedule['duration_minutes']);

        $otherTeacher = User::factory()->create();
        $otherCourse = $this->courseFor($otherTeacher, 'course-owned-by-other-teacher');

        $this->actingAs($teacher)
            ->from(route('admin.classes.index'))
            ->post(route('admin.classes.store'), [
                'name' => 'کلاس غیرمجاز',
                'course_id' => $otherCourse->id,
                'meeting_url' => 'https://www.skyroom.online/ch/academy/not-allowed',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('course_id');

        $this->assertDatabaseMissing('classrooms', ['name' => 'کلاس غیرمجاز']);
    }
}
