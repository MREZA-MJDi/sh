<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\IntegrationRun;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use App\Services\Quiz24\Contracts\Quiz24ResultData;
use App\Services\Quiz24\Quiz24ResultImporter;
use App\Services\SpotPlayer\SpotPlayerService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalServiceBusinessWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        Http::preventStrayRequests();
    }

    public function test_spotplayer_course_is_created_locally_and_waits_for_provider_contract(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.courses.store'), [
                'title' => 'دوره آزمایشی ریاضی',
                'type' => 'free',
                'price' => 0,
                'delivery_type' => 'spotplayer',
                'spotplayer_course_id' => 'SP-COURSE-101',
                'status' => 'published',
            ])
            ->assertRedirect();

        $course = Course::where('title', 'دوره آزمایشی ریاضی')->firstOrFail();

        $this->assertSame('awaiting_api_verification', $course->integration_status);
        $this->assertSame('SP-COURSE-101', $course->spotplayer_course_id);

        $this->assertDatabaseHas('integration_runs', [
            'provider' => 'spotplayer',
            'action' => 'verify_or_create_course_mapping',
            'status' => 'waiting_contract',
            'entity_id' => $course->id,
        ]);

        $this->assertDatabaseMissing('spot_player_licenses', ['course_id' => $course->id]);
        Http::assertNothingSent();
    }

    public function test_free_classroom_enrollment_can_queue_spotplayer_license_without_claiming_success(): void
    {
        $teacher = $this->createUserWithRole('teacher');
        $student = $this->createUserWithRole('student', ['phone' => '09120000000']);

        $course = Course::create([
            'title' => 'دوره رایگان ویدیویی',
            'slug' => 'free-video-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'spotplayer_course_id' => 'SP-FREE-10',
            'status' => 'published',
            'integration_status' => 'awaiting_api_verification',
        ]);

        $classroom = Classroom::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس تست',
            'status' => 'active',
        ]);

        $classroom->students()->attach($student->id, ['assigned_by' => $this->admin->id]);

        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $license = app(SpotPlayerService::class)->requestLicense($student, $course, $this->admin);

        $this->assertSame('waiting_contract', $license->status);
        $this->assertNull($license->external_license_id);
        $this->assertNull($license->license_key);

        $this->assertDatabaseHas('integration_runs', [
            'provider' => 'spotplayer',
            'action' => 'issue_license',
            'status' => 'waiting_contract',
            'entity_id' => $license->id,
        ]);

        Http::assertNothingSent();
    }

    public function test_paid_course_requires_verified_payment_before_spotplayer_access(): void
    {
        $teacher = $this->createUserWithRole('teacher');
        $student = $this->createUserWithRole('student');

        $course = Course::create([
            'title' => 'دوره پولی فیزیک',
            'slug' => 'paid-physics-course',
            'type' => 'paid',
            'price' => 250000,
            'delivery_type' => 'spotplayer',
            'spotplayer_course_id' => 'SP-PAID-22',
            'status' => 'published',
            'integration_status' => 'awaiting_api_verification',
        ]);

        $classroom = Classroom::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس پولی',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id, ['assigned_by' => $this->admin->id]);

        $enrollmentService = app(CourseEnrollmentService::class);
        $enrollmentService->syncClassroomRoster($classroom);

        $this->assertFalse($enrollmentService->hasActiveAccess($student, $course));
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'pending_payment',
        ]);

        $enrollmentService->grantAfterVerifiedPayment($student, $course, 'PAYMENT-VERIFIED-001');

        $this->assertTrue($enrollmentService->hasActiveAccess($student, $course));
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'payment',
            'status' => 'active',
            'source_reference' => 'PAYMENT-VERIFIED-001',
        ]);
    }

    public function test_quiz24_result_import_is_idempotent_and_unmatched_people_are_not_auto_assigned(): void
    {
        $student = $this->createUserWithRole('student', [
            'phone' => '09121112222',
            'external_student_id' => 'STU-EXT-22',
        ]);

        $exam = Exam::create([
            'title' => 'آزمون جامع آزمایشی',
            'provider' => 'quiz24',
            'external_id' => 'QUIZ-EXAM-11',
            'status' => 'published',
        ]);

        $importer = app(Quiz24ResultImporter::class);

        $knownResult = new Quiz24ResultData(
            resultId: 'RESULT-1',
            studentIdentifier: 'STU-EXT-22',
            studentName: 'دانش‌آموز تست',
            score: 17.5,
            maxScore: 20,
            submittedAt: now()->toIso8601String(),
        );

        $importer->import($exam, [$knownResult]);
        $importer->import($exam, [$knownResult]);

        $this->assertSame(1, ExamResult::where('exam_id', $exam->id)->where('student_id', $student->id)->count());

        $unknownResult = new Quiz24ResultData(
            resultId: 'RESULT-UNKNOWN',
            studentIdentifier: 'QUIZ24-UNKNOWN-900',
            studentName: 'شرکت‌کننده ناشناس',
            score: 12,
            maxScore: 20,
        );

        $importer->import($exam, [$unknownResult]);

        $participant = \App\Models\Quiz24UnmatchedParticipant::where('exam_id', $exam->id)
            ->where('external_identifier', 'QUIZ24-UNKNOWN-900')
            ->firstOrFail();

        $this->assertSame('unmatched', $participant->status);
        $this->assertNull($participant->resolved_user_id);

        $importer->resolveManually($participant, $student, $this->admin);

        $this->assertDatabaseHas('quiz24_student_mappings', [
            'provider' => 'quiz24',
            'external_identifier' => 'QUIZ24-UNKNOWN-900',
            'student_id' => $student->id,
        ]);

        $this->assertDatabaseHas('quiz24_unmatched_participants', [
            'id' => $participant->id,
            'status' => 'matched',
            'resolved_user_id' => $student->id,
        ]);

        $this->assertSame(1, ExamResult::where('exam_id', $exam->id)->where('student_id', $student->id)->count());
    }

    public function test_default_provider_gateways_report_contract_missing_and_do_not_send_requests(): void
    {
        $spotPlayer = app(\App\Services\SpotPlayer\SpotPlayerService::class);
        $quiz24 = app(\App\Services\Quiz24\Quiz24Service::class);

        $this->assertFalse($spotPlayer->health()->available);
        $this->assertSame('contract_missing', $spotPlayer->health()->state);
        $this->assertFalse($quiz24->health()->available);
        $this->assertSame('contract_missing', $quiz24->health()->state);

        $operation = $quiz24->requestExamCatalogSync($this->admin);

        $this->assertSame('waiting_contract', $operation->status);
        Http::assertNothingSent();
    }

    private function createUserWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            ...$attributes,
        ]);

        $user->roles()->attach(Role::where('name', $role)->value('id'));

        return $user;
    }
}
