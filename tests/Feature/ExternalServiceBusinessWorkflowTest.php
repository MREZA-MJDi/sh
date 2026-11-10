<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Quiz24StudentMapping;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use App\Services\Integrations\IntegrationException;
use App\Services\Quiz24\Contracts\Quiz24ResultData;
use App\Services\Quiz24\Quiz24Service;
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
            'action' => 'verify_course_mapping',
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

    public function test_paid_course_requires_verified_payment_and_unpublished_courses_suspend_access(): void
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

        $course->update(['status' => 'archived']);
        $enrollmentService->syncCourseRoster($course->refresh());

        $this->assertFalse($enrollmentService->hasActiveAccess($student, $course->refresh()));
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'payment',
            'status' => 'suspended',
            'source_reference' => 'PAYMENT-VERIFIED-001',
        ]);

        $course->update(['status' => 'published']);
        $enrollmentService->syncCourseRoster($course->refresh());

        $this->assertTrue($enrollmentService->hasActiveAccess($student, $course->refresh()));
    }

    public function test_quiz24_exam_is_created_locally_and_external_provisioning_is_queued(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.exams.store'), [
                'title' => 'آزمون جامع شیخان',
                'provider' => 'quiz24',
                'status' => 'draft',
            ])
            ->assertRedirect();

        $exam = Exam::where('title', 'آزمون جامع شیخان')->firstOrFail();

        $this->assertSame('waiting_external_mapping', $exam->integration_status);

        $this->assertDatabaseHas('integration_runs', [
            'provider' => 'quiz24',
            'action' => 'create_exam',
            'status' => 'waiting_contract',
            'entity_id' => $exam->id,
        ]);

        Http::assertNothingSent();
    }

    public function test_quiz24_sso_requires_student_exam_access_and_stable_external_identity(): void
    {
        $teacher = $this->createUserWithRole('teacher');
        $student = $this->createUserWithRole('student', [
            'external_student_id' => 'SH-STUDENT-501',
        ]);
        $otherStudent = $this->createUserWithRole('student', [
            'external_student_id' => 'SH-STUDENT-502',
        ]);

        $course = Course::create([
            'title' => 'دوره آزمون Quiz24',
            'slug' => 'quiz24-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس آزمون Quiz24',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id, ['assigned_by' => $this->admin->id]);

        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $exam = Exam::create([
            'title' => 'آزمون دسترسی‌دار Quiz24',
            'provider' => 'quiz24',
            'external_id' => 'Q24-ACCESS-101',
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'status' => 'published',
            'integration_status' => 'awaiting_api_verification',
        ]);

        $service = app(Quiz24Service::class);

        try {
            $service->requestStudentLaunch($exam, $otherStudent, '/student/exams/'.$exam->id);
            $this->fail('Student outside the assigned classroom must not receive an SSO launch request.');
        } catch (IntegrationException $exception) {
            $this->assertStringContainsString('عضو کلاس', $exception->getMessage());
        }

        $operation = $service->requestStudentLaunch($exam, $student, '/student/exams/'.$exam->id);

        $this->assertSame('waiting_contract', $operation->status);
        $this->assertDatabaseHas('integration_runs', [
            'provider' => 'quiz24',
            'action' => 'create_student_launch_url',
            'status' => 'waiting_contract',
            'entity_id' => $exam->id,
        ]);

        Http::assertNothingSent();
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

        $linkedStudent = $this->createUserWithRole('student', ['external_student_id' => 'SH-LINKED-900']);
        $importer->resolveManually($participant, $linkedStudent, $this->admin);

        $this->assertDatabaseHas('quiz24_student_mappings', [
            'provider' => 'quiz24',
            'external_identifier' => 'QUIZ24-UNKNOWN-900',
            'student_id' => $linkedStudent->id,
        ]);

        $this->assertDatabaseHas('quiz24_unmatched_participants', [
            'id' => $participant->id,
            'status' => 'matched',
            'resolved_user_id' => $linkedStudent->id,
        ]);

        $this->assertSame(1, ExamResult::where('exam_id', $exam->id)->where('student_id', $student->id)->count());
        $this->assertSame(1, ExamResult::where('exam_id', $exam->id)->where('student_id', $linkedStudent->id)->count());
    }

    public function test_default_provider_gateways_report_contract_missing_and_do_not_send_requests(): void
    {
        $spotPlayer = app(SpotPlayerService::class);
        $quiz24 = app(Quiz24Service::class);

        $this->assertFalse($spotPlayer->health()->available);
        $this->assertSame('contract_missing', $spotPlayer->health()->state);
        $this->assertFalse($quiz24->health()->available);
        $this->assertSame('contract_missing', $quiz24->health()->state);

        $operation = $quiz24->requestExamCatalogSync($this->admin);

        $this->assertSame('waiting_contract', $operation->status);
        Http::assertNothingSent();
    }


    public function test_spotplayer_video_is_staged_privately_and_upload_is_queued(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $course = Course::create([
            'title' => 'دوره ویدیویی تست',
            'slug' => 'spotplayer-video-staging-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'spotplayer_course_id' => 'SP-STAGING-01',
            'status' => 'published',
            'integration_status' => 'awaiting_api_verification',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.spotplayer.videos.store'), [
            'title' => 'جلسه اول',
            'course_id' => $course->id,
            'status' => 'waiting_contract',
            'file' => \Illuminate\Http\UploadedFile::fake()->create('lesson.mp4', 512, 'video/mp4'),
        ]);

        $response->assertRedirect();

        $video = \App\Models\SpotPlayerVideo::where('title', 'جلسه اول')->firstOrFail();

        $this->assertSame('waiting_contract', $video->status);
        $this->assertSame('lesson.mp4', $video->file_original_name);
        $this->assertNotNull($video->file_sha256);
        $this->assertSame(64, strlen($video->file_sha256));

        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($video->file_path);

        $this->assertDatabaseHas('integration_runs', [
            'provider' => 'spotplayer',
            'action' => 'upload_video',
            'status' => 'waiting_contract',
            'entity_id' => $video->id,
        ]);

        $this->get(route('admin.spotplayer.videos.download', $video))->assertOk();
        Http::assertNothingSent();
    }

    public function test_quiz24_import_does_not_assign_a_result_to_a_non_student_or_ambiguous_phone(): void
    {
        $parent = $this->createUserWithRole('parent', ['phone' => '09129990000']);
        $firstStudent = $this->createUserWithRole('student', ['phone' => '09128887777']);
        $secondStudent = $this->createUserWithRole('student', ['phone' => '09128887777']);

        $exam = Exam::create([
            'title' => 'آزمون کنترل هویت',
            'provider' => 'quiz24',
            'external_id' => 'QUIZ-IDENTITY-10',
            'status' => 'published',
        ]);

        Quiz24StudentMapping::create([
            'provider' => 'quiz24',
            'external_identifier' => 'PARENT-IDENTIFIER-10',
            'student_id' => $parent->id,
            'created_by' => $this->admin->id,
        ]);

        $importer = app(Quiz24ResultImporter::class);
        $summary = $importer->import($exam, [
            new Quiz24ResultData(
                resultId: 'RESULT-PARENT-10',
                studentIdentifier: 'PARENT-IDENTIFIER-10',
                studentName: 'حساب ولی',
                score: 14,
                maxScore: 20,
            ),
            new Quiz24ResultData(
                resultId: 'RESULT-AMBIGUOUS-10',
                studentIdentifier: '09128887777',
                studentName: 'شماره مشترک',
                score: 15,
                maxScore: 20,
            ),
        ]);

        $this->assertSame(0, $summary['matched']);
        $this->assertSame(2, $summary['unmatched']);
        $this->assertDatabaseMissing('exam_results', [
            'exam_id' => $exam->id,
            'student_id' => $parent->id,
        ]);
        $this->assertDatabaseMissing('exam_results', [
            'exam_id' => $exam->id,
            'student_id' => $firstStudent->id,
        ]);
        $this->assertDatabaseMissing('exam_results', [
            'exam_id' => $exam->id,
            'student_id' => $secondStudent->id,
        ]);
        $this->assertDatabaseHas('quiz24_unmatched_participants', [
            'exam_id' => $exam->id,
            'external_identifier' => 'PARENT-IDENTIFIER-10',
            'status' => 'unmatched',
        ]);
        $this->assertDatabaseHas('quiz24_unmatched_participants', [
            'exam_id' => $exam->id,
            'external_identifier' => '09128887777',
            'status' => 'unmatched',
        ]);
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
