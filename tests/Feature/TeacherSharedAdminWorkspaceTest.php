<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
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

    public function test_admin_assigns_teacher_navigation_permissions_and_courses_in_the_create_drawer(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره قابل تخصیص',
            'slug' => 'teacher-permission-assignment-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'skyroom',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.teachers.index'))
            ->assertOk()
            ->assertSee('ساخت مدرس')
            ->assertSee('دسترسی‌ها')
            ->assertSee('مدیریت کلاس و کلاس آنلاین اسکای‌روم')
            ->assertSee('ساخت و مدیریت آزمون‌ها');

        $this->actingAs($admin)
            ->post(route('admin.teachers.store'), [
                'name' => 'مدرس دسترسی‌دار',
                'phone' => '09120000077',
                'subject' => 'ریاضی',
                'status' => 'active',
                'password' => 'StrongTeacherPassword!2026',
                'password_confirmation' => 'StrongTeacherPassword!2026',
                'course_ids' => [$course->id],
                'teacher_access' => ['classes', 'exams', 'materials_homework'],
            ])
            ->assertSessionHasNoErrors();

        $teacher = User::query()->where('phone', '09120000077')->firstOrFail();

        $this->assertTrue($teacher->permissions_customized);
        $this->assertTrue($teacher->hasPermissionTo('dashboard.view'));
        $this->assertTrue($teacher->hasPermissionTo('profile.update'));
        $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
        $this->assertTrue($teacher->hasPermissionTo('classes.manage.assigned'));
        $this->assertTrue($teacher->hasPermissionTo('exams.manage.assigned'));
        $this->assertTrue($teacher->hasPermissionTo('homework.manage.assigned'));
        $this->assertFalse($teacher->hasPermissionTo('attendance.manage.assigned'));
        $this->assertFalse($teacher->hasPermissionTo('integrations.manage'));
        $this->assertSame($teacher->id, $course->refresh()->teacher_id);
    }

    public function test_teacher_can_create_internal_exam_only_for_an_assigned_class_or_course(): void
    {
        $teacher = $this->teacherWithPermissions(['exams.manage.assigned']);
        $course = $this->courseFor($teacher, 'teacher-exam-course');
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس آزمون مدرس',
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.exams.index'))
            ->assertOk()
            ->assertSee('آزمون‌ها')
            ->assertSee('آزمون جدید');

        $this->actingAs($teacher)
            ->post(route('admin.exams.store'), [
                'title' => 'آزمون داخلی مدرس',
                'classroom_id' => $classroom->id,
                'course_id' => $course->id,
                'provider' => 'internal',
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'duration_hours' => 2,
                'show_to_guardians' => 1,
                'status' => 'published',
                'description' => 'آزمون دوره خود مدرس',
            ])
            ->assertSessionHasNoErrors();

        $exam = Exam::query()->where('title', 'آزمون داخلی مدرس')->firstOrFail();
        $this->assertSame(120, $exam->duration_minutes);
        $this->assertTrue($exam->show_to_guardians);
        $this->assertSame($teacher->id, $exam->classroom->teacher_id);

        $otherTeacher = User::factory()->create();
        $otherCourse = $this->courseFor($otherTeacher, 'teacher-exam-other-course');
        $otherClassroom = Classroom::query()->create([
            'course_id' => $otherCourse->id,
            'teacher_id' => $otherTeacher->id,
            'name' => 'کلاس مدرس دیگر',
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->from(route('admin.exams.index'))
            ->post(route('admin.exams.store'), [
                'title' => 'آزمون غیرمجاز',
                'classroom_id' => $otherClassroom->id,
                'course_id' => $otherCourse->id,
                'provider' => 'internal',
                'duration_hours' => 1,
                'show_to_guardians' => 0,
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('classroom_id');

        $this->assertDatabaseMissing('exams', ['title' => 'آزمون غیرمجاز']);
    }

    public function test_teacher_can_create_and_grade_homework_only_inside_assigned_classes(): void
    {
        $teacher = $this->teacherWithPermissions(['homework.manage.assigned', 'homework.grade']);
        $course = $this->courseFor($teacher, 'teacher-homework-course');
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس تکلیف مدرس',
            'status' => 'active',
        ]);

        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $classroom->students()->attach($student->id);

        $this->actingAs($teacher)
            ->post(route('admin.homework.store'), [
                'classroom_id' => $classroom->id,
                'title' => 'تکلیف قابل بررسی',
                'instructions' => 'حل تمرین‌های فصل اول',
                'due_at' => now()->addDays(2)->toDateTimeString(),
                'status' => 'published',
            ])
            ->assertSessionHasNoErrors();

        $homework = Homework::query()->where('title', 'تکلیف قابل بررسی')->firstOrFail();
        $submission = HomeworkSubmission::query()->create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'answer' => 'پاسخ دانش‌آموز',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->patch(route('admin.homework.review', $submission), [
                'score' => 18,
                'feedback' => 'پاسخ درست است؛ چند مرحله را کامل‌تر بنویس.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('homework_submissions', [
            'id' => $submission->id,
            'status' => 'reviewed',
            'score' => 18,
            'reviewed_by' => $teacher->id,
        ]);

        $otherTeacher = User::factory()->create();
        $otherCourse = $this->courseFor($otherTeacher, 'teacher-homework-other-course');
        $otherClassroom = Classroom::query()->create([
            'course_id' => $otherCourse->id,
            'teacher_id' => $otherTeacher->id,
            'name' => 'کلاس تکلیف مدرس دیگر',
            'status' => 'active',
        ]);
        $otherHomework = Homework::query()->create([
            'classroom_id' => $otherClassroom->id,
            'title' => 'تکلیف محرمانه کلاس دیگر',
            'status' => 'published',
        ]);
        $otherSubmission = HomeworkSubmission::query()->create([
            'homework_id' => $otherHomework->id,
            'student_id' => $student->id,
            'answer' => 'پاسخ محرمانه',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->patch(route('admin.homework.review', $otherSubmission), [
                'score' => 19,
                'feedback' => 'تلاش خوب',
            ])
            ->assertForbidden();
    }


    public function test_customized_teacher_can_open_the_dashboard_after_admin_creates_the_account(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'name' => 'مدرس تازه',
            'phone' => '09120000088',
            'subject' => 'علوم',
            'status' => 'active',
            'password' => 'StrongTeacherPassword!2026',
            'password_confirmation' => 'StrongTeacherPassword!2026',
            'teacher_access' => ['attendance'],
        ])->assertSessionHasNoErrors();

        $teacher = User::query()->where('phone', '09120000088')->firstOrFail();

        $this->assertTrue($teacher->hasPermissionTo('dashboard.view'));
        $this->assertTrue($teacher->hasPermissionTo('profile.update'));

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('کلاس‌ها و فعالیت‌های شما');
    }

    public function test_homework_submission_list_is_paginated_instead_of_truncated_at_thirty(): void
    {
        $teacher = $this->teacherWithPermissions(['homework.view.assigned']);
        $course = $this->courseFor($teacher, 'teacher-homework-pagination-course');
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس صفحه‌بندی تکالیف',
            'status' => 'active',
        ]);
        $homework = Homework::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'تکلیف صفحه‌بندی',
            'status' => 'published',
        ]);

        for ($index = 1; $index <= 16; $index++) {
            $student = User::factory()->create(['name' => 'دانش‌آموز قدیمی '.$index]);
            $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));
            HomeworkSubmission::query()->create([
                'homework_id' => $homework->id,
                'student_id' => $student->id,
                'answer' => 'پاسخ '.$index,
                'status' => 'submitted',
                'submitted_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($teacher)
            ->get(route('admin.homework.index', ['submissions' => 2]))
            ->assertOk()
            ->assertSee('دانش‌آموز قدیمی ۱۶');
    }

}
