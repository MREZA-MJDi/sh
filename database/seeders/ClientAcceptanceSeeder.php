<?php

namespace Database\Seeders;

use App\Models\AcademicGrade;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\LearningResource;
use App\Models\Lesson;
use App\Models\Message;
use App\Models\MessageReply;
use App\Models\ParentMeeting;
use App\Models\Question;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * One-shot, opt-in acceptance fixtures. Never included in DatabaseSeeder.
 * No edits to existing records, no external calls and no fake processed media.
 */
final class ClientAcceptanceSeeder extends Seeder
{
    public function run(): void
    {
        if (getenv('SHIKHAN_QA_SEED') !== '1' || (
            ! app()->environment(['local', 'testing'])
            && rtrim((string) config('app.url'), '/') !== 'http://185.7.212.87:8088'
        )) {
            throw new RuntimeException('Acceptance seeding is restricted to the explicitly enabled test environment.');
        }

        $password = getenv('SHIKHAN_QA_PASSWORD');
        if (! is_string($password) || mb_strlen($password) < 12) {
            throw new RuntimeException('Supply a temporary QA password of at least 12 characters.');
        }

        // A second invocation must not recreate edited/deleted fixtures.
        if (User::query()->where('email', 'like', 'qa.acceptance.%@example.test')->exists()
            || Course::query()->where('slug', 'like', 'qa-acceptance-%')->exists()
            || User::query()->where('email', 'like', '%@academy.example.test')->exists()
            || Course::query()->where('slug', 'like', 'demo-academy-%')->exists()) {
            $this->command?->warn('QA fixtures already exist. No records were modified.');
            return;
        }

        $admin = User::query()->where('email', (string) getenv('SHIKHAN_QA_ADMIN_EMAIL'))
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->firstOrFail();
        $roles = Role::query()->whereIn('name', ['teacher', 'student', 'parent'])->pluck('id', 'name');
        if ($roles->count() !== 3) {
            throw new RuntimeException('Initialize academy roles before running acceptance fixtures.');
        }

        $pdfPath = 'private/resources/demo-academy-'.Str::uuid().'.pdf';
        $productPath = 'private/resources/demo-academy-'.Str::uuid().'.pdf';
        $disk = Storage::disk('local');
        try {
            DB::transaction(function () use ($password, $roles, $admin, $disk, $pdfPath, $productPath): void {
                $grade = AcademicGrade::query()->where('is_active', true)->orderBy('sort_order')->firstOrFail();
                $account = function (string $key, string $name, string $role) use ($password, $roles, $grade): User {
                    $user = User::query()->create([
                        'email' => $key.'@academy.example.test',
                        'name' => $name,
                        'password' => $password,
                        'is_active' => true,
                        'grade' => $role === 'student' ? $grade->name : null,
                        'academic_grade_id' => $role === 'student' ? $grade->id : null,
                    ]);
                    $user->roles()->attach($roles[$role]);
                    return $user;
                };

                $teachers = [
                    $account('amir.rad', 'امیر رادمنش', 'teacher'),
                    $account('sara.nouri', 'سارا نوریان', 'teacher'),
                ];
                $students = [
                    $account('armin.mehri', 'آرمین مهری', 'student'),
                    $account('nima.mehri', 'نیما مهری', 'student'),
                    $account('helia.karimi', 'هلیا کریمیان', 'student'),
                    $account('parsa.rostami', 'پارسا رستمی', 'student'),
                ];
                $parents = [
                    $account('maryam.mehri', 'مریم مهری', 'parent'),
                    $account('reza.karimi', 'رضا کریمیان', 'parent'),
                ];
                $parents[0]->children()->attach([$students[0]->id, $students[1]->id]);
                $parents[1]->children()->attach($students[2]->id);

                $now = now();
                $features = ['recorded_video' => true, 'live_class' => true, 'resources' => true,
                    'assignments' => true, 'exams' => true, 'projects' => false];

                foreach (['ریاضی و حل مسئله', 'فیزیک و تمرین'] as $i => $subject) {
                    $teacher = $teachers[$i];
                    $members = $i === 0 ? [$students[0], $students[1]] : [$students[2]];
                    $course = Course::query()->create([
                        'slug' => 'demo-academy-'.($i === 0 ? 'math-problem-solving' : 'physics-concepts'), 'title' => $subject,
                        'description' => $i === 0 ? 'آموزش مفهومی ریاضی، حل تمرین‌های طبقه‌بندی‌شده و آمادگی آزمون پایان فصل.' : 'آموزش مفاهیم فیزیک با مثال‌های کاربردی، تمرین و تحلیل مسئله.',
                        'subject' => $subject, 'teacher_id' => $teacher->id,
                        'grade' => $grade->name, 'academic_grade_id' => $grade->id, 'season' => 'autumn',
                        'type' => 'free', 'price' => 0, 'delivery_type' => 'skyroom',
                        'status' => 'published', 'learning_features' => $features,
                        'integration_status' => 'not_required',
                    ]);
                    $class = Classroom::query()->create([
                        'course_id' => $course->id, 'teacher_id' => $teacher->id,
                        'name' => $i === 0 ? 'ریاضی تکمیلی ـ گروه الف' : 'فیزیک مفهومی ـ گروه ب', 'status' => 'active',
                        'schedule' => [['day' => 'شنبه', 'start' => '17:00', 'end' => '18:00', 'label' => 'آموزش و حل تمرین']],
                        'created_by' => $admin->id,
                    ]);
                    $class->students()->attach(array_map(fn (User $user) => $user->id, $members), ['assigned_by' => $admin->id]);
                    app(CourseEnrollmentService::class)->syncClassroomRoster($class);

                    $section = CourseSection::query()->create([
                        'course_id' => $course->id, 'title' => 'فصل اول ـ مفاهیم پایه',
                        'sort_order' => 1, 'status' => 'published',
                    ]);
                    Lesson::query()->create([
                        'course_section_id' => $section->id, 'title' => 'آشنایی با مبانی',
                        'body' => 'مفهوم را بخوانید و یک مثال حل کنید.',
                        'duration_minutes' => 20, 'sort_order' => 1, 'status' => 'published',
                    ]);
                    $past = ClassSession::query()->create([
                        'classroom_id' => $class->id, 'title' => 'مرور مفاهیم و حل مثال',
                        'session_type' => 'onsite', 'starts_at' => $now->copy()->subDays(2),
                        'ends_at' => $now->copy()->subDays(2)->addHour(),
                        'status' => 'completed', 'created_by' => $teacher->id,
                    ]);
                    foreach (['حل تمرین‌های فصل اول', 'رفع اشکال و مرور هفتگی'] as $j => $title) {
                        $start = $now->copy()->addDays($j + 1);
                        ClassSession::query()->create([
                            'classroom_id' => $class->id, 'title' => $title,
                            'session_type' => 'online', 'meeting_provider' => 'skyroom',
                            'meeting_url' => null, 'starts_at' => $start, 'ends_at' => $start->copy()->addHour(),
                            'status' => 'scheduled', 'created_by' => $teacher->id,
                        ]);
                    }
                    foreach ($members as $j => $student) {
                        Attendance::query()->create([
                            'classroom_id' => $class->id, 'class_session_id' => $past->id,
                            'student_id' => $student->id, 'session_date' => $past->starts_at->toDateString(),
                            'status' => $j === 0 ? 'present' : 'absent', 'marked_by' => $teacher->id,
                        ]);
                    }

                    $homework = Homework::query()->create([
                        'classroom_id' => $class->id, 'title' => 'تمرین‌های مفهومی فصل اول',
                        'instructions' => 'پاسخ متنی یا فایل تصویر، PDF و Word ارسال کنید.',
                        'due_at' => $now->copy()->addDays(4), 'status' => 'published', 'created_by' => $teacher->id,
                    ]);
                    HomeworkSubmission::query()->create([
                        'homework_id' => $homework->id, 'student_id' => $members[0]->id,
                        'answer' => 'پاسخ مرحله به مرحله و بررسی نتیجه.', 'score' => 18 - $i,
                        'feedback' => 'روش حل درست است؛ توضیح نهایی را کامل‌تر کن.',
                        'status' => 'reviewed', 'submitted_at' => $now->copy()->subDay(),
                        'reviewed_by' => $teacher->id, 'reviewed_at' => $now->copy()->subHours(2),
                    ]);
                    if (isset($members[1])) {
                        HomeworkSubmission::query()->create([
                            'homework_id' => $homework->id, 'student_id' => $members[1]->id,
                            'answer' => 'پاسخ برای بررسی مدرس.', 'status' => 'submitted',
                            'submitted_at' => $now->copy()->subHour(),
                        ]);
                    }
                    Homework::query()->create([
                        'classroom_id' => $class->id, 'title' => 'تمرین تکمیلی آخر هفته',
                        'instructions' => 'تمرین‌های تکمیلی پس از جلسه رفع اشکال منتشر می‌شوند.', 'status' => 'draft', 'created_by' => $teacher->id,
                    ]);

                    $exam = Exam::query()->create([
                        'classroom_id' => $class->id, 'course_id' => $course->id,
                        'created_by' => $teacher->id, 'title' => 'آزمون پایان فصل اول',
                        'provider' => 'internal', 'starts_at' => $now->copy()->subDays(3),
                        'duration_minutes' => 30, 'attempts_allowed' => 1, 'show_to_guardians' => true,
                        'status' => 'closed', 'integration_status' => 'not_required',
                    ]);
                    ExamResult::query()->create([
                        'exam_id' => $exam->id, 'student_id' => $members[0]->id,
                        'score' => 17 + $i, 'max_score' => 20,
                        'synced_at' => $now->copy()->subDays(2), 'feedback' => 'تسلط خوب بر مفاهیم؛ تمرین‌های ترکیبی بیشتر مرور شوند.',
                    ]);
                    $draft = Exam::query()->create([
                        'classroom_id' => $class->id, 'course_id' => $course->id,
                        'created_by' => $teacher->id, 'title' => 'آزمون جمع‌بندی ماهانه',
                        'provider' => 'internal', 'starts_at' => $now->copy()->addDays(5),
                        'duration_minutes' => 20, 'attempts_allowed' => 1, 'show_to_guardians' => true,
                        'status' => 'draft', 'integration_status' => 'not_required',
                    ]);
                    Question::query()->create([
                        'exam_id' => $draft->id, 'question' => 'حاصل ۷ × ۸ چیست؟', 'type' => 'single',
                        'options' => ['54', '56', '64', '48'], 'correct_answer' => '56', 'score' => 5, 'sort_order' => 1,
                    ]);
                    Question::query()->create([
                        'exam_id' => $draft->id, 'question' => 'روش حل را توضیح دهید.', 'type' => 'descriptive',
                        'score' => 15, 'sort_order' => 2,
                    ]);

                    $parent = $parents[$i];
                    ParentMeeting::query()->create([
                        'classroom_id' => $class->id, 'course_id' => $course->id,
                        'student_id' => $members[0]->id, 'guardian_id' => $parent->id,
                        'created_by' => $parent->id, 'title' => 'جلسه بررسی روند تحصیلی',
                        'message' => 'درخواست بررسی روند آموزشی.', 'preferred_at' => $now->copy()->addDays(3),
                        'status' => 'requested',
                    ]);
                    Consultation::query()->create([
                        'requester_id' => $parent->id, 'student_id' => $members[0]->id,
                        'teacher_id' => $teacher->id, 'type' => 'ticket',
                        'topic' => 'برنامه‌ریزی و پیشرفت تحصیلی', 'notes' => 'درخواست بررسی تمرین‌ها.',
                        'status' => 'pending',
                    ]);
                    $message = Message::query()->create([
                        'sender_id' => $parent->id, 'recipient_id' => $teacher->id,
                        'student_id' => $members[0]->id, 'subject' => 'پیگیری تمرین‌های این هفته',
                        'body' => 'لطفاً وضعیت تکلیف را بررسی کنید.', 'status' => 'answered',
                        'replied_at' => $now->copy()->subMinutes(10),
                    ]);
                    MessageReply::query()->create([
                        'message_id' => $message->id, 'sender_id' => $teacher->id,
                        'body' => 'نمره و بازخورد در کارتابل قرار گرفت.',
                    ]);
                    Message::query()->create([
                        'sender_id' => $parent->id, 'recipient_id' => $admin->id,
                        'student_id' => $members[0]->id, 'subject' => 'هماهنگی برنامه کلاس',
                        'body' => 'درخواست بررسی برنامه کلاس.', 'status' => 'open',
                    ]);

                    if ($i === 0) {
                        if (! $disk->put($pdfPath, $this->samplePdf())) {
                            throw new RuntimeException('Could not write private QA PDF.');
                        }
                        LearningResource::query()->create([
                            'course_id' => $course->id, 'classroom_id' => $class->id,
                            'uploaded_by' => $teacher->id, 'title' => 'جزوه مفاهیم پایه و حل مسئله',
                            'description' => 'مرور نکته‌های اصلی فصل و راهنمای حل تمرین برای اعضای کلاس.',
                            'kind' => 'pdf', 'is_free' => true, 'price' => 0, 'visibility' => 'enrolled',
                            'downloadable' => true, 'file_path' => $pdfPath, 'status' => 'published',
                        ]);
                        if (! $disk->put($productPath, $this->samplePdf())) {
                            throw new RuntimeException('Could not write private QA product PDF.');
                        }
                        LearningResource::query()->create([
                            'course_id' => $course->id, 'classroom_id' => $class->id,
                            'uploaded_by' => $teacher->id, 'title' => 'دفتر تمرین ریاضی و حل مسئله',
                            'description' => 'مجموعه تمرین‌های طبقه‌بندی‌شده برای مرور و آمادگی آزمون.',
                            'kind' => 'pdf', 'is_free' => false, 'price' => 25000, 'visibility' => 'enrolled',
                            'downloadable' => true, 'file_path' => $productPath, 'status' => 'published',
                        ]);
                        SpotPlayerVideo::query()->create([
                            'course_id' => $course->id, 'classroom_id' => $class->id,
                            'created_by' => $teacher->id, 'title' => 'آموزش مفاهیم پایه ـ جلسه اول',
                            'status' => 'draft',
                        ]);
                    }
                }

                Course::query()->create([
                    'slug' => 'demo-academy-study-skills', 'title' => 'مهارت‌های مطالعه و مدیریت زمان',
                    'type' => 'paid', 'price' => 150000, 'delivery_type' => 'onsite',
                    'status' => 'draft', 'learning_features' => $features,
                ]);
                Announcement::query()->create([
                    'created_by' => $admin->id, 'title' => 'برنامه کلاس‌های هفته آینده',
                    'body' => 'برنامه جلسات هفته آینده در کارتابل آموزشی قابل مشاهده است. لطفاً پیش از هر جلسه تمرین‌های مربوط را مرور کنید.',
                    'audience' => 'students', 'status' => 'published',
                    'priority' => 'normal', 'published_at' => $now,
                ]);
                Announcement::query()->create([
                    'created_by' => $admin->id, 'title' => 'زمان‌بندی آزمون‌های ماه آینده',
                    'body' => 'جزئیات زمان‌بندی پس از نهایی شدن برنامه مدرسین منتشر خواهد شد.', 'audience' => 'all', 'status' => 'draft',
                ]);
            });
        } catch (Throwable $exception) {
            $disk->delete([$pdfPath, $productPath]);
            throw $exception;
        }

        $this->command?->info('Academy sample records created. Use the @academy.example.test accounts with your supplied password.');
    }

    /** Valid one-page PDF, including cross-reference offsets; no external download. */
    private function samplePdf(): string
    {
        $stream = "BT /F1 18 Tf 50 750 Td (Mathematics - Concepts and practice) Tj ET\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
