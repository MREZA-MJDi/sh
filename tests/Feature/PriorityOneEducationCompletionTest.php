<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityOneEducationCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }

    private function enrolledClass(User $student, User $teacher, string $slug): array
    {
        $course = Course::query()->create([
            'title' => 'دوره '.$slug,
            'slug' => $slug,
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس '.$slug,
            'status' => 'active',
        ]);

        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        return [$course, $classroom];
    }

    public function test_student_and_parent_can_see_course_and_lesson_progress(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $parent = $this->userWithRole('parent');
        $parent->children()->attach($student->id);

        [$course] = $this->enrolledClass($student, $teacher, 'progress-course');
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'title' => 'بخش اول',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        $lesson = Lesson::query()->create([
            'course_section_id' => $section->id,
            'title' => 'درس اول',
            'body' => 'محتوای درس آزمایشی',
            'status' => 'published',
            'sort_order' => 1,
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertOk()
            ->assertSee('درس اول');

        $this->actingAs($student)
            ->patch(route('student.courses.lessons.progress', [$course, $lesson]), [
                'progress_percent' => 100,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lesson_progress', [
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'progress_percent' => 100,
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('پیشرفت دوره‌های من')
            ->assertSee('۱ از ۱ درس تکمیل‌شده');

        $this->actingAs($parent)
            ->get(route('guardian.dashboard', ['child' => $student->id]))
            ->assertOk()
            ->assertSee('پیشرفت دوره‌ها و درس‌ها')
            ->assertSee('۱ از ۱ درس تکمیل‌شده');
    }

    public function test_reviewed_homework_score_and_feedback_are_visible_to_student_and_linked_parent(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $parent = $this->userWithRole('parent');
        $parent->children()->attach($student->id);

        [, $classroom] = $this->enrolledClass($student, $teacher, 'reviewed-homework-course');
        $homework = Homework::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'تمرین فصل اول',
            'instructions' => 'پاسخ تمرین را ارسال کن.',
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);
        HomeworkSubmission::query()->create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'answer' => 'پاسخ دانش‌آموز',
            'score' => 18,
            'feedback' => 'راه‌حل درست است؛ محاسبات را مرتب‌تر بنویس.',
            'status' => 'reviewed',
            'submitted_at' => now()->subHour(),
            'reviewed_by' => $teacher->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('نتیجه بررسی مدرس')
            ->assertSee('۱۸')
            ->assertSee('راه‌حل درست است؛ محاسبات را مرتب‌تر بنویس.');

        $this->actingAs($parent)
            ->get(route('guardian.dashboard', ['child' => $student->id]))
            ->assertOk()
            ->assertSee('نتایج بررسی تکالیف')
            ->assertSee('تمرین فصل اول')
            ->assertSee('راه‌حل درست است؛ محاسبات را مرتب‌تر بنویس.');
    }

    public function test_admin_consultation_statuses_are_shared_with_parent_portal(): void
    {
        $admin = $this->userWithRole('admin');
        $student = $this->userWithRole('student');
        $parent = $this->userWithRole('parent');
        $parent->children()->attach($student->id);

        $consultation = Consultation::query()->create([
            'requester_id' => $parent->id,
            'student_id' => $student->id,
            'type' => 'phone',
            'topic' => 'پیگیری آموزشی',
            'status' => 'pending',
            'notes' => 'درباره برنامه درسی',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.consultations.update', $consultation), [
                'status' => 'scheduled',
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'notes' => 'زمان تماس هماهنگ شد.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($parent)
            ->get(route('guardian.consultations.index'))
            ->assertOk()
            ->assertSee('هماهنگ‌شده');

        $this->actingAs($admin)
            ->patch(route('admin.consultations.update', $consultation), [
                'status' => 'done',
                'notes' => 'تماس انجام شد.',
            ])
            ->assertRedirect();

        $this->actingAs($parent)
            ->get(route('guardian.consultations.show', $consultation))
            ->assertOk()
            ->assertSee('انجام‌شده');
    }

    public function test_internal_exam_can_be_taken_graded_and_seen_by_student_and_parent(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $parent = $this->userWithRole('parent');
        $parent->children()->attach($student->id);

        [$course, $classroom] = $this->enrolledClass($student, $teacher, 'internal-exam-course');
        $exam = Exam::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'آزمون فصل اول',
            'provider' => 'internal',
            'status' => 'published',
            'duration_minutes' => 60,
            'attempts_allowed' => 1,
            'show_to_guardians' => true,
        ]);

        $objective = Question::query()->create([
            'exam_id' => $exam->id,
            'type' => 'single',
            'question' => 'حاصل ۲ + ۲ چیست؟',
            'options' => ['۳', '۴', '۵'],
            'correct_answer' => '۴',
            'score' => 1,
            'sort_order' => 1,
        ]);
        $written = Question::query()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'question' => 'روش حل را توضیح بده.',
            'options' => null,
            'correct_answer' => null,
            'score' => 4,
            'sort_order' => 2,
        ]);

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('آزمون فصل اول');

        $this->actingAs($student)
            ->get(route('student.exams.show', $exam))
            ->assertOk()
            ->assertSee('شروع آزمون');

        $response = $this->actingAs($student)
            ->post(route('student.exams.start', $exam))
            ->assertRedirect();

        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->where('student_id', $student->id)->firstOrFail();
        $this->assertSame(route('student.exams.attempt', [$exam, $attempt]), $response->headers->get('Location'));

        $this->actingAs($student)
            ->get(route('student.exams.attempt', [$exam, $attempt]))
            ->assertOk()
            ->assertSee('حاصل ۲ + ۲ چیست؟')
            ->assertSee('روش حل را توضیح بده.');

        $this->actingAs($student)
            ->post(route('student.exam-attempts.submit', $attempt), [
                'answers' => [
                    $objective->id => '۴',
                    $written->id => 'ابتدا دو واحد را با دو واحد دیگر جمع می‌کنیم.',
                ],
            ])
            ->assertRedirect(route('student.exams.result', $exam));

        $attempt->refresh();
        $this->assertSame('pending_review', $attempt->status);
        $this->assertDatabaseHas('exam_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $objective->id,
            'score' => 1,
        ]);

        $writtenAnswer = ExamAnswer::query()
            ->where('exam_attempt_id', $attempt->id)
            ->where('question_id', $written->id)
            ->firstOrFail();

        $this->actingAs($teacher)
            ->get(route('admin.exams.attempts', $exam))
            ->assertOk()
            ->assertSee('آزمون فصل اول')
            ->assertSee('ابتدا دو واحد را با دو واحد دیگر جمع می‌کنیم.');

        $this->actingAs($teacher)
            ->patch(route('admin.exams.attempts.grade', [$exam, $attempt]), [
                'scores' => [$writtenAnswer->id => 4],
                'feedback' => 'استدلال صحیح و کامل بود.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('exam_results', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'score' => 5,
            'max_score' => 5,
            'feedback' => 'استدلال صحیح و کامل بود.',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('آزمون فصل اول')
            ->assertSee('استدلال صحیح و کامل بود.');

        $this->actingAs($parent)
            ->get(route('guardian.dashboard', ['child' => $student->id]))
            ->assertOk()
            ->assertSee('آزمون فصل اول')
            ->assertSee('استدلال صحیح و کامل بود.');

        $this->assertSame('graded', $attempt->fresh()->status);
    }

    public function test_student_cannot_open_an_exam_for_another_class(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $otherStudent = $this->userWithRole('student');

        [$course, $classroom] = $this->enrolledClass($student, $teacher, 'private-exam-course');
        $exam = Exam::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'آزمون خصوصی کلاس',
            'provider' => 'internal',
            'status' => 'published',
        ]);

        $this->actingAs($otherStudent)
            ->get(route('student.exams.show', $exam))
            ->assertNotFound();
    }
}
