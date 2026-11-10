<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\LearningResource;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_can_create_student_account_with_role(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'دانش‌آموز آزمایشی',
            'email' => 'student@example.test',
            'phone' => '09120000000',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'grade' => 'دوازدهم',
        ])->assertRedirect();

        $student = User::where('email', 'student@example.test')->firstOrFail();

        $this->assertTrue($student->hasRole('student'));
        $this->assertSame('دوازدهم', $student->grade);
    }

    public function test_admin_can_update_learning_resource_and_paid_resources_need_a_price(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $this->actingAs($admin)->post(route('admin.resources.store'), [
            'title' => 'جزوه فیزیک',
            'kind' => 'pdf',
            'is_free' => '0',
            'price' => 125000,
            'visibility' => 'enrolled',
            'status' => 'published',
        ])->assertRedirect();

        $resource = LearningResource::where('title', 'جزوه فیزیک')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.resources.update', $resource), [
            'title' => 'جزوه فیزیک پیشرفته',
            'kind' => 'pdf',
            'is_free' => '0',
            'price' => 175000,
            'visibility' => 'authenticated',
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseHas('learning_resources', [
            'id' => $resource->id,
            'title' => 'جزوه فیزیک پیشرفته',
            'price' => 175000,
            'visibility' => 'authenticated',
            'status' => 'draft',
        ]);

        $this->from(route('admin.resources.index'))
            ->post(route('admin.resources.store'), [
                'title' => 'محصول بدون قیمت',
                'kind' => 'book',
                'is_free' => '0',
                'price' => 0,
                'visibility' => 'public',
                'status' => 'published',
            ])
            ->assertSessionHasErrors('price');

        $this->assertDatabaseMissing('learning_resources', ['title' => 'محصول بدون قیمت']);
    }

    public function test_admin_can_edit_exam_and_open_paginated_results_page(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $exam = Exam::create([
            'title' => 'آزمون اولیه',
            'provider' => 'internal',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)->patch(route('admin.exams.update', $exam), [
            'title' => 'آزمون نهایی ریاضی',
            'provider' => 'internal',
            'status' => 'published',
            'description' => 'آزمون پایان فصل',
        ])->assertRedirect();

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'title' => 'آزمون نهایی ریاضی',
            'status' => 'published',
            'integration_status' => 'not_required',
        ]);

        $this->get(route('admin.exams.results', $exam))
            ->assertOk()
            ->assertSee('آزمون نهایی ریاضی')
            ->assertSee('هنوز نتیجه‌ای برای این آزمون ثبت نشده است.');
    }

    public function test_classroom_edit_reconciles_student_roster_and_course_access(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->roles()->attach(Role::where('name', 'teacher')->value('id'));

        $firstStudent = User::factory()->create(['is_active' => true]);
        $firstStudent->roles()->attach(Role::where('name', 'student')->value('id'));

        $secondStudent = User::factory()->create(['is_active' => true]);
        $secondStudent->roles()->attach(Role::where('name', 'student')->value('id'));

        $course = Course::create([
            'title' => 'دوره رایگان تست',
            'slug' => 'free-course-classroom-edit',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس اولیه',
            'schedule' => ['days' => ['sat'], 'time' => '10:00'],
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $classroom->students()->attach($firstStudent->id, ['assigned_by' => $admin->id]);

        app(\App\Services\Courses\CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $this->actingAs($admin)->patch(route('admin.classes.update', $classroom), [
            'name' => 'کلاس به‌روزشده',
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'schedule_days' => ['mon', 'wed'],
            'schedule_time' => '18:30',
            'student_ids' => [$secondStudent->id],
        ])->assertRedirect();

        $classroom->refresh();

        $this->assertSame(['mon', 'wed'], $classroom->schedule['days']);
        $this->assertSame('18:30', $classroom->schedule['time']);
        $this->assertDatabaseMissing('classroom_student', [
            'classroom_id' => $classroom->id,
            'student_id' => $firstStudent->id,
        ]);
        $this->assertDatabaseHas('classroom_student', [
            'classroom_id' => $classroom->id,
            'student_id' => $secondStudent->id,
        ]);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $firstStudent->id,
            'status' => 'revoked',
        ]);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $secondStudent->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_published_course(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $this->actingAs($admin)->post(route('admin.courses.store'), [
            'title' => 'دوره ریاضی دوازدهم',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'status' => 'published',
        ])->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'title' => 'دوره ریاضی دوازدهم',
            'type' => 'free',
            'status' => 'published',
            'price' => 0,
        ]);
    }
}
