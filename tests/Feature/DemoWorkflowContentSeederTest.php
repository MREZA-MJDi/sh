<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Message;
use App\Models\SeoArticle;
use App\Models\User;
use Database\Seeders\DemoWorkflowContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoWorkflowContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_workflow_seeder_is_repeatable_and_preserves_admin_edits(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@sh.local']);
        $student = User::factory()->create(['email' => 'student@sh.local']);
        $parent = User::factory()->create(['email' => 'parent@sh.local']);

        $this->seed(DemoWorkflowContentSeeder::class);

        $videoOnly = Course::query()->where('slug', 'video-only-demo-course')->firstOrFail();
        $this->assertFalse($videoOnly->featureEnabled('live_class'));
        $this->assertTrue($videoOnly->featureEnabled('recorded_video'));
        $this->assertSame(0, $videoOnly->classrooms()->count());

        $this->assertGreaterThanOrEqual(2, ClassSession::query()->count());
        $this->assertDatabaseHas('homeworks', [
            'title' => 'تمرین متنی بدون فایل ضمیمه',
            'attachment_path' => null,
        ]);
        $this->assertDatabaseHas('homework_submissions', [
            'student_id' => $student->id,
            'attachment_path' => null,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('exam_attempts', [
            'student_id' => $student->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $student->id,
            'recipient_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('consultations', [
            'requester_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('seo_articles', [
            'slug' => 'how-to-choose-online-course',
            'status' => 'published',
        ]);

        $videoOnly->update(['title' => 'عنوان ویرایش‌شده توسط مدیر']);
        $courseCount = Course::query()->count();
        $sessionCount = ClassSession::query()->count();
        $homeworkCount = Homework::query()->count();
        $examCount = Exam::query()->count();
        $attemptCount = ExamAttempt::query()->count();
        $submissionCount = HomeworkSubmission::query()->count();
        $messageCount = Message::query()->count();
        $articleCount = SeoArticle::query()->count();
        $consultationCount = Consultation::query()->count();

        $this->seed(DemoWorkflowContentSeeder::class);

        $this->assertSame($courseCount, Course::query()->count());
        $this->assertSame($sessionCount, ClassSession::query()->count());
        $this->assertSame($homeworkCount, Homework::query()->count());
        $this->assertSame($examCount, Exam::query()->count());
        $this->assertSame($attemptCount, ExamAttempt::query()->count());
        $this->assertSame($submissionCount, HomeworkSubmission::query()->count());
        $this->assertSame($messageCount, Message::query()->count());
        $this->assertSame($articleCount, SeoArticle::query()->count());
        $this->assertSame($consultationCount, Consultation::query()->count());
        $this->assertDatabaseHas('courses', [
            'slug' => 'video-only-demo-course',
            'title' => 'عنوان ویرایش‌شده توسط مدیر',
        ]);
    }
}
