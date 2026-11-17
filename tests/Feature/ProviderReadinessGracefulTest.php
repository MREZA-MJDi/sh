<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Role;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\Learning\LearningAccessService;
use App\Services\StudentExamService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ProviderReadinessGracefulTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->admin = $this->userWithRole('admin');
    }

    public function test_spotplayer_license_request_without_external_mapping_stays_pending_without_http_error(): void
    {
        $student = $this->userWithRole('student', ['phone' => '09120000000']);
        $course = $this->course('video-without-provider-map', ['recorded_video' => true], 'onsite');

        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_by' => $this->admin->id,
            'granted_at' => now(),
        ]);

        $this->actingAs($student)
            ->post(route('student.courses.spotplayer-license.store', $course))
            ->assertRedirect()
            ->assertSessionHas('status');

        $license = SpotPlayerLicense::where('student_id', $student->id)->where('course_id', $course->id)->firstOrFail();

        $this->assertSame('waiting_contract', $license->status);
        $this->assertNull($license->external_license_id);
        $this->assertNull($license->license_key);
        $this->assertStringContainsString('شناسه دوره', $license->error_message);

        $this->assertDatabaseMissing('integration_runs', [
            'provider' => 'spotplayer',
            'action' => 'issue_license',
            'entity_id' => $license->id,
        ]);
        Http::assertNothingSent();
    }

    public function test_course_access_feature_filter_is_centralized_and_respected_by_quiz_fallback(): void
    {
        $student = $this->userWithRole('student');
        $examCourse = $this->course('exam-feature-enabled', ['exams' => true]);
        $noExamCourse = $this->course('exam-feature-disabled', ['exams' => false]);
        $defaultExamCourse = $this->course('exam-feature-default', ['recorded_video' => true]);

        foreach ([$examCourse, $noExamCourse, $defaultExamCourse] as $course) {
            CourseEnrollment::create([
                'course_id' => $course->id,
                'student_id' => $student->id,
                'source' => 'manual',
                'status' => 'active',
                'granted_by' => $this->admin->id,
                'granted_at' => now(),
            ]);
        }

        $courseIds = app(LearningAccessService::class)
            ->activeCourseIds($student, 'exams')
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertContains($examCourse->id, $courseIds);
        $this->assertContains($defaultExamCourse->id, $courseIds);
        $this->assertNotContains($noExamCourse->id, $courseIds);

        $exam = Exam::create([
            'course_id' => $examCourse->id,
            'created_by' => $this->admin->id,
            'title' => 'آزمون داخلی تا اتصال Quiz24',
            'provider' => 'quiz24',
            'status' => 'published',
            'integration_status' => 'waiting_contract',
        ]);

        Question::create([
            'exam_id' => $exam->id,
            'type' => 'single',
            'question' => 'دو به‌علاوه دو چند می‌شود؟',
            'options' => ['۳', '۴'],
            'correct_answer' => '۴',
            'score' => 1,
            'sort_order' => 1,
        ]);

        $visibleExams = app(StudentExamService::class)->query($student)->get();

        $this->assertTrue($visibleExams->contains('id', $exam->id));
        $this->assertSame('waiting_contract', $visibleExams->firstWhere('id', $exam->id)->integration_status);
        Http::assertNothingSent();
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::where('name', $role)->value('id'));

        return $user;
    }

    private function course(string $slug, array $features, string $delivery = 'onsite'): Course
    {
        return Course::create([
            'title' => 'دوره '.$slug,
            'slug' => $slug,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => $delivery,
            'learning_features' => $features,
            'status' => 'published',
        ]);
    }
}
