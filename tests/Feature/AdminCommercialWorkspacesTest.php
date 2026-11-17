<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminCommercialWorkspacesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        $this->admin = $this->userWithRole('admin');
    }

    public function test_parent_workspace_supports_editing_existing_parent_accounts(): void
    {
        $parent = $this->userWithRole('parent', ['email' => 'parent@example.test']);
        $student = $this->userWithRole('student');

        $this->actingAs($this->admin)->patch(route('admin.parents.update', $parent), [
            'name' => 'ولی ویرایش‌شده',
            'email' => 'parent@example.test',
            'phone' => '09120000000',
            'child_ids' => [$student->id],
        ])->assertRedirect();

        $this->assertSame('ولی ویرایش‌شده', $parent->fresh()->name);
        $this->assertTrue($parent->fresh()->children()->whereKey($student->id)->exists());
    }

    public function test_admin_can_grant_and_revoke_a_manual_course_enrollment(): void
    {
        $student = $this->userWithRole('student');
        $course = $this->course('manual-enrollment-course');

        $this->actingAs($this->admin)->post(route('admin.enrollments.store'), ['student_id' => $student->id, 'course_id' => $course->id])->assertRedirect();
        $enrollment = CourseEnrollment::where('student_id', $student->id)->where('course_id', $course->id)->firstOrFail();

        $this->assertSame('manual', $enrollment->source);
        $this->assertSame('active', $enrollment->status);
        $this->assertSame($this->admin->id, $enrollment->granted_by);

        $this->actingAs($this->admin)->patch(route('admin.enrollments.revoke', $enrollment))->assertRedirect();
        $this->assertSame('revoked', $enrollment->fresh()->status);
    }

    public function test_manual_payment_requires_review_before_course_access_is_granted(): void
    {
        $student = $this->userWithRole('student');
        $course = $this->course('manual-payment-course', 'paid', 250000);

        $this->actingAs($this->admin)->post(route('admin.payments.store'), [
            'student_id' => $student->id, 'course_id' => $course->id, 'amount' => 250000,
            'method' => 'bank_transfer', 'provider_reference' => 'BANK-RECEIPT-001',
        ])->assertRedirect();

        $payment = Payment::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('pending_review', $payment->status);
        $this->assertDatabaseMissing('course_enrollments', ['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        $this->actingAs($this->admin)->patch(route('admin.payments.verify', $payment))->assertRedirect();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseHas('course_enrollments', ['student_id' => $student->id, 'course_id' => $course->id, 'source' => 'payment', 'status' => 'active']);
    }

    public function test_online_class_workspace_reads_real_class_sessions(): void
    {
        $teacher = $this->userWithRole('teacher');
        $course = $this->course('online-session-course');
        $classroom = Classroom::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'name' => 'کلاس آنلاین واقعی', 'status' => 'active']);
        ClassSession::create([
            'classroom_id' => $classroom->id, 'title' => 'جلسه آنلاین واقعی', 'session_type' => 'online',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
            'meeting_provider' => 'skyroom', 'status' => 'scheduled', 'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->get(route('admin.online-classes.index'))
            ->assertOk()->assertSee('جلسه آنلاین واقعی')->assertSee('در انتظار فعال‌سازی');
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::where('name', $role)->value('id'));
        return $user;
    }

    private function course(string $slug, string $type = 'free', int $price = 0): Course
    {
        return Course::create(['title' => 'دوره '.$slug, 'slug' => $slug, 'type' => $type, 'price' => $price, 'status' => 'published', 'delivery_type' => 'onsite']);
    }
}
