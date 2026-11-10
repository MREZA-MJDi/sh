<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class LegacyAcademyImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();

        config([
            'database.connections.legacy_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);
        DB::purge('legacy_test');
        $this->createLegacySchema();
        $this->seedLegacyRows();
    }

    public function test_import_defaults_to_dry_run_and_does_not_change_target_database(): void
    {
        $exit = Artisan::call('sh:import-legacy-academy', [
            '--connection' => 'legacy_test',
            '--legacy-academy-id' => 71,
        ]);

        $this->assertSame(0, $exit, Artisan::output());
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(0, DB::table('courses')->count());
        $this->assertSame(0, DB::table('legacy_import_maps')->count());
    }

    public function test_apply_imports_accounts_guardians_courses_classes_and_verified_enrollments_idempotently(): void
    {
        $exit = Artisan::call('sh:import-legacy-academy', [
            '--connection' => 'legacy_test',
            '--legacy-academy-id' => 71,
            '--apply' => true,
        ]);

        $this->assertSame(0, $exit, Artisan::output());
        $this->assertSame(3, DB::table('users')->count());
        $this->assertDatabaseHas('role_user', [
            'user_id' => DB::table('users')->where('phone', '09120000001')->value('id'),
            'role_id' => Role::query()->where('name', 'student')->value('id'),
        ]);

        $studentId = DB::table('users')->where('phone', '09120000001')->value('id');
        $parentId = DB::table('users')->where('phone', '09120000003')->value('id');
        $courseId = DB::table('courses')->where('slug', 'physics-legacy-501')->value('id');
        $classroomId = DB::table('classrooms')->where('name', 'کلاس فیزیک')->value('id');

        $this->assertNotNull($courseId);
        $this->assertNotNull($classroomId);
        $this->assertDatabaseHas('guardian_student', [
            'guardian_id' => $parentId,
            'student_id' => $studentId,
        ]);
        $this->assertDatabaseHas('classroom_student', [
            'classroom_id' => $classroomId,
            'student_id' => $studentId,
        ]);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $courseId,
            'student_id' => $studentId,
            'source' => 'legacy_import',
            'status' => 'active',
        ]);

        // Re-running the import must not duplicate the mapped records.
        $exit = Artisan::call('sh:import-legacy-academy', [
            '--connection' => 'legacy_test',
            '--legacy-academy-id' => 71,
            '--apply' => true,
        ]);

        $this->assertSame(0, $exit, Artisan::output());
        $this->assertSame(3, DB::table('users')->count());
        $this->assertSame(1, DB::table('courses')->count());
        $this->assertSame(1, DB::table('classrooms')->count());
        $this->assertSame(1, DB::table('course_enrollments')->count());
    }

    public function test_legacy_phpass_password_is_upgraded_on_mobile_login_after_import(): void
    {
        Artisan::call('sh:import-legacy-academy', [
            '--connection' => 'legacy_test',
            '--legacy-academy-id' => 71,
            '--apply' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => '09120000001',
            'password' => 'legacy-import-password',
        ]);

        $student = User::query()->where('phone', '09120000001')->firstOrFail();

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($student);
        $this->assertTrue(Hash::check('legacy-import-password', $student->fresh()->password));
        $this->assertStringStartsWith('$2y$', $student->fresh()->password);
    }

    private function createLegacySchema(): void
    {
        $schema = Schema::connection('legacy_test');

        $schema->create('users', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('mobile')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        $schema->create('roles', function ($table): void {
            $table->increments('id');
            $table->string('slug');
        });
        $schema->create('role_user', function ($table): void {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
        });
        $schema->create('academy_user', function ($table): void {
            $table->unsignedInteger('academy_id');
            $table->unsignedInteger('user_id');
            $table->string('role');
            $table->string('status')->default('active');
        });
        $schema->create('parent_student', function ($table): void {
            $table->unsignedInteger('parent_id');
            $table->unsignedInteger('student_id');
            $table->string('relation')->nullable();
        });
        $schema->create('courses', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('academy_id');
            $table->unsignedInteger('created_by')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('level')->nullable();
            $table->string('status')->default('draft');
            $table->string('access_type')->default('free');
            $table->decimal('price', 14, 2)->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->string('spotplayer_course_id')->nullable();
            $table->timestamps();
        });
        $schema->create('course_teacher', function ($table): void {
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('teacher_id');
            $table->boolean('is_primary')->default(false);
        });
        $schema->create('classrooms', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('academy_id');
            $table->unsignedInteger('course_id');
            $table->string('title');
            $table->string('code');
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
        $schema->create('classroom_teacher', function ($table): void {
            $table->unsignedInteger('classroom_id');
            $table->unsignedInteger('teacher_id');
        });
        $schema->create('classroom_student', function ($table): void {
            $table->unsignedInteger('classroom_id');
            $table->unsignedInteger('student_id');
            $table->string('status')->default('active');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
        $schema->create('course_enrollments', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('classroom_id')->nullable();
            $table->string('status')->default('active');
            $table->string('payment_status')->default('unpaid');
            $table->decimal('price_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
        $schema->create('financial_transactions', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('enrollment_id')->nullable();
            $table->string('status')->default('completed');
            $table->decimal('amount', 14, 2);
        });
    }

    private function seedLegacyRows(): void
    {
        $db = DB::connection('legacy_test');
        $db->table('roles')->insert([
            ['id' => 1, 'slug' => 'student'],
            ['id' => 2, 'slug' => 'teacher'],
            ['id' => 3, 'slug' => 'parent'],
        ]);

        $db->table('users')->insert([
            [
                'id' => 11, 'name' => 'دانش‌آموز منتقل‌شده', 'email' => 'student-legacy@example.test',
                'password' => '$P$B12345678KrcV6c7XbhUsava2BrG7b.', 'mobile' => '09120000001',
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 12, 'name' => 'مدرس منتقل‌شده', 'email' => 'teacher-legacy@example.test',
                'password' => Hash::make('TeacherLegacyPassword!2026'), 'mobile' => '09120000002',
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 13, 'name' => 'ولی منتقل‌شده', 'email' => 'parent-legacy@example.test',
                'password' => Hash::make('ParentLegacyPassword!2026'), 'mobile' => '09120000003',
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $db->table('role_user')->insert([
            ['user_id' => 11, 'role_id' => 1],
            ['user_id' => 12, 'role_id' => 2],
            ['user_id' => 13, 'role_id' => 3],
        ]);
        $db->table('academy_user')->insert([
            ['academy_id' => 71, 'user_id' => 11, 'role' => 'student', 'status' => 'active'],
            ['academy_id' => 71, 'user_id' => 12, 'role' => 'teacher', 'status' => 'active'],
        ]);
        $db->table('parent_student')->insert([
            ['parent_id' => 13, 'student_id' => 11, 'relation' => 'parent'],
        ]);

        $db->table('courses')->insert([
            'id' => 501, 'academy_id' => 71, 'created_by' => 12,
            'title' => 'دوره فیزیک', 'slug' => 'physics', 'description' => 'شرح دوره',
            'level' => 'دهم', 'status' => 'published', 'access_type' => 'free',
            'price' => 0, 'duration_minutes' => 900, 'published_at' => now()->subDay(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $db->table('course_teacher')->insert([
            'course_id' => 501, 'teacher_id' => 12, 'is_primary' => true,
        ]);
        $db->table('classrooms')->insert([
            'id' => 601, 'academy_id' => 71, 'course_id' => 501, 'title' => 'کلاس فیزیک',
            'code' => 'PHY-1', 'status' => 'active', 'created_by' => 12,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $db->table('classroom_teacher')->insert([
            'classroom_id' => 601, 'teacher_id' => 12,
        ]);
        $db->table('classroom_student')->insert([
            'classroom_id' => 601, 'student_id' => 11, 'status' => 'active',
        ]);
        $db->table('course_enrollments')->insert([
            'id' => 701, 'course_id' => 501, 'student_id' => 11,
            'classroom_id' => 601, 'status' => 'active',
            'payment_status' => 'paid', 'price_amount' => 0, 'paid_amount' => 0,
            'started_at' => now()->subMonth(),
        ]);
    }
}
