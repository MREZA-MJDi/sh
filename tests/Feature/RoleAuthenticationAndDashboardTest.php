<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use App\Services\StudentDashboardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAuthenticationAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_guest_can_open_login_and_student_registration_pages(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('ورود به حساب کاربری');
        $this->get(route('register'))->assertOk()->assertSee('ثبت‌نام دانش‌آموز');
    }

    public function test_public_registration_creates_only_a_student_account_even_when_role_is_tampered(): void
    {
        $this->post(route('register.store'), [
            'name' => 'دانش‌آموز جدید',
            'email' => 'new.student@example.test',
            'phone' => '09120000000',
            'password' => 'StrongStudentPassword!2026',
            'password_confirmation' => 'StrongStudentPassword!2026',
            'role' => 'admin',
        ])->assertRedirect(route('student.dashboard'));

        $student = User::query()->where('email', 'new.student@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($student);
        $this->assertTrue($student->hasRole('student'));
        $this->assertFalse($student->hasRole('admin'));
        $this->assertDatabaseHas('role_user', [
            'user_id' => $student->id,
            'role_id' => Role::query()->where('name', 'student')->value('id'),
        ]);
    }

    public function test_login_redirects_each_role_to_its_own_dashboard(): void
    {
        $accounts = [
            'admin' => ['email' => 'role.admin@example.test', 'route' => 'admin.dashboard'],
            'teacher' => ['email' => 'role.teacher@example.test', 'route' => 'admin.dashboard'],
            'student' => ['email' => 'role.student@example.test', 'route' => 'student.dashboard'],
            'parent' => ['email' => 'role.parent@example.test', 'route' => 'guardian.dashboard'],
        ];

        foreach ($accounts as $roleName => $account) {
            $user = User::factory()->create([
                'email' => $account['email'],
                'password' => 'StrongLoginPassword!2026',
                'is_active' => true,
            ]);
            $user->roles()->attach(Role::query()->where('name', $roleName)->value('id'));

            $this->post(route('login.store'), [
                'email' => $account['email'],
                'password' => 'StrongLoginPassword!2026',
            ])->assertRedirect(route($account['route']));

            $this->assertAuthenticatedAs($user);
            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_teacher_dashboard_shows_only_teacher_navigation_and_scoped_classes(): void
    {
        $teacher = User::factory()->create(['name' => 'مدرس اول']);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $otherTeacher = User::factory()->create(['name' => 'مدرس دوم']);
        $otherTeacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره مدرس اول',
            'slug' => 'teacher-dashboard-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس قابل مشاهده مدرس اول',
            'status' => 'active',
        ]);

        $otherCourse = Course::query()->create([
            'title' => 'دوره مدرس دوم',
            'slug' => 'other-teacher-dashboard-course',
            'teacher_id' => $otherTeacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        Classroom::query()->create([
            'course_id' => $otherCourse->id,
            'teacher_id' => $otherTeacher->id,
            'name' => 'کلاس محرمانه مدرس دوم',
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('کلاس قابل مشاهده مدرس اول')
            ->assertDontSee('کلاس محرمانه مدرس دوم')
            ->assertSee('داشبورد من')
            ->assertDontSee('کوییز ۲۴')
            ->assertDontSee('مدیریت کاربران');
    }

    public function test_student_dashboard_does_not_expose_admin_navigation_or_another_students_class(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره دانش‌آموز',
            'slug' => 'student-dashboard-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $enrolled = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس ثبت‌شده دانش‌آموز',
            'status' => 'active',
        ]);
        $enrolled->students()->attach($student->id);

        app(CourseEnrollmentService::class)->syncClassroomRoster($enrolled);

        Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس دانش‌آموز دیگر',
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('کلاس ثبت‌شده دانش‌آموز')
            ->assertDontSee('کلاس دانش‌آموز دیگر')
            ->assertSee('تکالیف من')
            ->assertDontSee('اسپات پلیر')
            ->assertDontSee('کوییز ۲۴');
    }

    public function test_student_dashboard_does_not_show_class_after_course_enrollment_is_revoked(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $teacher = User::factory()->create();
        $course = Course::query()->create([
            'title' => 'دوره لغوشده',
            'slug' => 'revoked-student-dashboard-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس با ثبت‌نام لغوشده',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $student->courseEnrollments()
            ->where('course_id', $course->id)
            ->update(['status' => 'revoked']);

        $dashboard = app(StudentDashboardService::class)->build($student);

        $this->assertSame(0, $dashboard['classroom_count']);
        $this->assertTrue($dashboard['classrooms']->isEmpty());
    }

    public function test_student_results_only_include_own_class_or_accessible_course_level_exams(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $course = Course::query()->create([
            'title' => 'دوره مجاز دانش‌آموز',
            'slug' => 'student-result-accessible-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس مجاز نتایج',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $otherCourse = Course::query()->create([
            'title' => 'دوره کلاس دیگر',
            'slug' => 'student-result-other-course',
            'teacher_id' => $otherTeacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $otherClassroom = Classroom::query()->create([
            'course_id' => $otherCourse->id,
            'teacher_id' => $otherTeacher->id,
            'name' => 'کلاس غیرمجاز نتایج',
            'status' => 'active',
        ]);

        $courseLevelExam = Exam::query()->create([
            'course_id' => $course->id,
            'created_by' => $teacher->id,
            'title' => 'آزمون سطح دوره مجاز',
            'provider' => 'internal',
            'status' => 'published',
        ]);
        ExamResult::query()->create([
            'exam_id' => $courseLevelExam->id,
            'student_id' => $student->id,
            'score' => 18,
            'max_score' => 20,
        ]);

        $otherClassExam = Exam::query()->create([
            'classroom_id' => $otherClassroom->id,
            'course_id' => $otherCourse->id,
            'created_by' => $otherTeacher->id,
            'title' => 'نتیجه کلاس غیرمجاز',
            'provider' => 'internal',
            'status' => 'published',
        ]);
        ExamResult::query()->create([
            'exam_id' => $otherClassExam->id,
            'student_id' => $student->id,
            'score' => 19,
            'max_score' => 20,
        ]);

        $dashboard = app(StudentDashboardService::class)->build($student);
        $titles = $dashboard['results']->map(fn (ExamResult $result) => $result->exam?->title)->all();

        $this->assertContains('آزمون سطح دوره مجاز', $titles);
        $this->assertNotContains('نتیجه کلاس غیرمجاز', $titles);
    }

    public function test_deactivated_users_cannot_keep_access_to_a_role_dashboard(): void
    {
        $teacher = User::factory()->create(['is_active' => false]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertForbidden();
    }

    public function test_roles_cannot_open_another_roles_dashboard(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $this->actingAs($student)->get(route('teacher.dashboard'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.dashboard'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_demo_accounts_are_created_only_when_explicitly_enabled_in_local_or_testing(): void
    {
        config([
            'demo.enabled' => true,
            'demo.password' => 'LocalOnlyDemoPassword!2026',
        ]);

        $this->seed(\Database\Seeders\LocalDemoAccountsSeeder::class);

        foreach ([
            'admin@sh.local' => 'admin',
            'teacher@sh.local' => 'teacher',
            'student@sh.local' => 'student',
            'parent@sh.local' => 'parent',
        ] as $email => $roleName) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertTrue($user->hasRole($roleName));
            $this->assertTrue(Hash::check('LocalOnlyDemoPassword!2026', $user->password));
        }
    }
}
