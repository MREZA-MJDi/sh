<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use App\Models\ContactMessage;
use App\Models\Consultation;
use App\Models\ConsultationReply;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Message;
use App\Models\MessageReply;
use App\Models\Question;
use App\Models\SeoArticle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stable, editable demo fixtures for the complete academy workflow.
 *
 * Uses firstOrCreate intentionally: rerunning seeders never overwrites edits
 * made by an administrator. All records are normal application records and
 * can be managed through the existing admin workspaces.
 */
final class DemoWorkflowContentSeeder extends Seeder
{
    public function run(): void
    {
        $enabled = app()->environment(['local', 'testing'])
            || (app()->environment('production')
                && config('demo.enabled')
                && config('demo.seed_academic_content'));

        if (! $enabled) {
            return;
        }

        $teacher = User::query()->where('email', 'teacher@sh.local')->first();
        $student = User::query()->where('email', 'student@sh.local')->first();
        $parent = User::query()->where('email', 'parent@sh.local')->first();

        if (! $teacher || ! $student) {
            $this->command?->warn('داده‌های گردش‌کار نمایشی ساخته نشد؛ حساب مدرس و دانش‌آموز آزمایشی لازم است.');

            return;
        }

        DB::transaction(function () use ($teacher, $student, $parent): void {
            $now = Carbon::now();
            $course = Course::query()->firstOrCreate(
                ['slug' => 'video-only-demo-course'],
                [
                    'title' => 'دوره ویدئویی بدون کلاس؛ مهارت مطالعه مؤثر',
                    'description' => 'یک دوره خودآموز شامل ویدئوی معرفی، درس‌های ویدئویی، محتوای متنی و منابع PDF اختیاری. این دوره برنامه کلاس یا جلسه آنلاین ندارد.',
                    'subject' => 'مهارت‌های مطالعه',
                    'grade' => $student->grade ?: 'عمومی',
                    'teacher_id' => $teacher->id,
                    'type' => 'free',
                    'price' => 0,
                    'delivery_type' => 'spotplayer',
                    'status' => 'published',
                    'integration_status' => 'not_required',
                    'learning_features' => [
                        'recorded_video' => true,
                        'live_class' => false,
                        'resources' => true,
                        'assignments' => true,
                        'exams' => true,
                    ],
                ]
            );

            $liveCourse = Course::query()->firstOrCreate(
                ['slug' => 'hybrid-live-demo-course'],
                [
                    'title' => 'دوره ترکیبی با کلاس آنلاین؛ ریاضی و حل مسئله',
                    'description' => 'نمونه دوره دارای محتوای ضبط‌شده و جلسات آنلاین برنامه‌ریزی‌شده. سرویس جلسه زنده تا زمان دریافت اطلاعات رسمی در وضعیت انتظار باقی می‌ماند.',
                    'subject' => 'ریاضی',
                    'grade' => $student->grade ?: 'هفتم',
                    'teacher_id' => $teacher->id,
                    'type' => 'free',
                    'price' => 0,
                    'delivery_type' => 'skyroom',
                    'status' => 'published',
                    'integration_status' => 'pending',
                    'integration_note' => 'اطلاعات اتصال سرویس جلسه آنلاین هنوز از کارفرما دریافت نشده است.',
                    'learning_features' => [
                        'recorded_video' => true,
                        'live_class' => true,
                        'resources' => true,
                        'assignments' => true,
                        'exams' => true,
                    ],
                ]
            );

            $classroom = $liveCourse->classrooms()->firstOrCreate(
                ['name' => 'کلاس آنلاین نمونه - گروه اول'],
                [
                    'teacher_id' => $teacher->id,
                    'schedule' => [
                        ['day' => 'شنبه', 'start' => '17:00', 'end' => '18:30', 'label' => 'آموزش و حل مسئله'],
                        ['day' => 'دوشنبه', 'start' => '17:00', 'end' => '18:00', 'label' => 'رفع اشکال'],
                    ],
                    'meeting_url' => null,
                    'status' => 'active',
                    'created_by' => $teacher->id,
                ]
            );

            $classroom->students()->syncWithoutDetaching([
                $student->id => ['assigned_by' => $teacher->id],
            ]);

            ClassSession::query()->firstOrCreate(
                ['classroom_id' => $classroom->id, 'title' => 'جلسه آنلاین برگزارشده نمونه'],
                [
                    'session_type' => 'online',
                    'starts_at' => $now->copy()->subDays(3)->setTime(17, 0),
                    'ends_at' => $now->copy()->subDays(3)->setTime(18, 0),
                    'meeting_provider' => 'skyroom',
                    'meeting_url' => null,
                    'status' => 'completed',
                    'teacher_notes' => 'جلسه آزمایشی برای بررسی حضور و غیاب و گزارش مدرس؛ لینک واقعی جلسه ثبت نشده است.',
                    'created_by' => $teacher->id,
                ]
            );

            ClassSession::query()->firstOrCreate(
                ['classroom_id' => $classroom->id, 'title' => 'جلسه آنلاین آینده نمونه'],
                [
                    'session_type' => 'online',
                    'starts_at' => $now->copy()->addDays(2)->setTime(17, 0),
                    'ends_at' => $now->copy()->addDays(2)->setTime(18, 0),
                    'meeting_provider' => 'skyroom',
                    'meeting_url' => null,
                    'status' => 'scheduled',
                    'teacher_notes' => 'زمان‌بندی آماده است؛ لینک جلسه پس از تنظیم سرویس توسط کارفرما ثبت می‌شود.',
                    'created_by' => $teacher->id,
                ]
            );

            ClassSession::query()->firstOrCreate(
                ['classroom_id' => $classroom->id, 'title' => 'جلسه حضوری نمونه'],
                [
                    'session_type' => 'in_person',
                    'starts_at' => $now->copy()->addDays(5)->setTime(16, 0),
                    'ends_at' => $now->copy()->addDays(5)->setTime(17, 0),
                    'meeting_provider' => null,
                    'meeting_url' => null,
                    'status' => 'scheduled',
                    'teacher_notes' => 'نمونه جلسه حضوری برای نمایش تفاوت نوع جلسه با کلاس آنلاین.',
                    'created_by' => $teacher->id,
                ]
            );

            $homework = Homework::query()->firstOrCreate(
                ['classroom_id' => $classroom->id, 'title' => 'تمرین متنی بدون فایل ضمیمه'],
                [
                    'instructions' => 'پاسخ را در کادر متنی بنویسید. بارگذاری PDF یا تصویر اجباری نیست؛ می‌توانید پاسخ را فقط به‌صورت متن ارسال کنید.',
                    'due_at' => $now->copy()->addDays(4),
                    'attachment_path' => null,
                    'status' => 'published',
                    'created_by' => $teacher->id,
                ]
            );

            HomeworkSubmission::query()->firstOrCreate(
                ['homework_id' => $homework->id, 'student_id' => $student->id],
                [
                    'answer' => 'ابتدا داده‌های مسئله را مشخص می‌کنم، سپس راه‌حل را مرحله‌به‌مرحله می‌نویسم و در پایان پاسخ را بررسی می‌کنم.',
                    'attachment_path' => null,
                    'score' => null,
                    'feedback' => null,
                    'status' => 'submitted',
                    'submitted_at' => $now->copy()->subHours(5),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]
            );

            $exam = Exam::query()->firstOrCreate(
                ['course_id' => $liveCourse->id, 'title' => 'آزمون داخلی نمونه؛ پاسخ تشریحی و تستی'],
                [
                    'classroom_id' => $classroom->id,
                    'created_by' => $teacher->id,
                    'description' => 'آزمون داخلی نمونه برای تست سؤال تستی، تشریحی، ثبت پاسخ دانش‌آموز و بازبینی مدرس؛ وابسته به Quiz24 نیست.',
                    'provider' => 'internal',
                    'external_id' => null,
                    'starts_at' => $now->copy()->subDay(),
                    'duration_minutes' => 35,
                    'attempts_allowed' => 2,
                    'show_to_guardians' => true,
                    'status' => 'published',
                    'integration_status' => 'not_required',
                    'integration_note' => null,
                ]
            );

            $choice = Question::query()->firstOrCreate(
                ['exam_id' => $exam->id, 'question' => 'حاصل ۷ × ۸ کدام است؟'],
                [
                    'type' => 'single',
                    'options' => ['۵۴', '۵۶', '۶۴', '۴۸'],
                    'correct_answer' => '۵۶',
                    'score' => 5,
                    'sort_order' => 1,
                ]
            );

            $written = Question::query()->firstOrCreate(
                ['exam_id' => $exam->id, 'question' => 'روش حل یک مسئله چندمرحله‌ای را با زبان خود توضیح دهید.'],
                [
                    'type' => 'descriptive',
                    'options' => null,
                    'correct_answer' => null,
                    'score' => 15,
                    'sort_order' => 2,
                ]
            );

            $attempt = ExamAttempt::query()->firstOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $student->id, 'attempt_number' => 1],
                [
                    'started_at' => $now->copy()->subHours(3),
                    'submitted_at' => $now->copy()->subHours(2),
                    'score' => null,
                    'max_score' => 20,
                    'status' => 'submitted',
                    'feedback' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]
            );

            ExamAnswer::query()->firstOrCreate(
                ['exam_attempt_id' => $attempt->id, 'question_id' => $choice->id],
                ['answer' => '۵۶', 'is_correct' => true, 'score' => 5]
            );
            ExamAnswer::query()->firstOrCreate(
                ['exam_attempt_id' => $attempt->id, 'question_id' => $written->id],
                ['answer' => 'ابتدا صورت سؤال را می‌خوانم، داده‌ها و خواسته را جدا می‌کنم، راهبرد مناسب را انتخاب می‌کنم و پاسخ را کنترل می‌کنم.', 'is_correct' => null, 'score' => null]
            );

            if ($parent) {
                $consultation = Consultation::query()->firstOrCreate(
                    ['requester_id' => $parent->id, 'student_id' => $student->id, 'topic' => 'درخواست مشاوره درباره روند تحصیلی'],
                    [
                        'teacher_id' => $teacher->id,
                        'handled_by' => null,
                        'type' => 'online',
                        'contact_phone' => $parent->phone,
                        'desired_at' => $now->copy()->addDays(2),
                        'scheduled_at' => null,
                        'status' => 'pending',
                        'notes' => 'درخواست آزمایشی؛ هنوز توسط مدرس تعیین وقت نشده است.',
                    ]
                );

                ConsultationReply::query()->firstOrCreate(
                    ['consultation_id' => $consultation->id, 'sender_id' => $teacher->id],
                    ['body' => 'درخواست شما دریافت شد. پس از بررسی برنامه مدرس، زمان پیشنهادی از همین بخش اعلام می‌شود.']
                );
            }

            $message = Message::query()->firstOrCreate(
                [
                    'sender_id' => $student->id,
                    'recipient_id' => $teacher->id,
                    'student_id' => $student->id,
                    'subject' => 'سؤال درباره شیوه ارسال تکلیف',
                ],
                [
                    'parent_id' => null,
                    'body' => 'اگر تکلیف فایل ضمیمه نداشته باشد، می‌توانم پاسخ را فقط به‌صورت متن ارسال کنم؟',
                    'status' => 'replied',
                    'replied_at' => $now->copy()->subHour(),
                ]
            );

            MessageReply::query()->firstOrCreate(
                ['message_id' => $message->id, 'sender_id' => $teacher->id],
                ['body' => 'بله؛ اگر تکلیف فایل اجباری نداشته باشد، پاسخ متنی به‌تنهایی قابل ثبت و بررسی است.']
            );

            ContactMessage::query()->firstOrCreate(
                ['name' => 'کاربر آزمایشی', 'contact' => 'demo@example.test', 'topic' => 'مشاوره دوره'],
                [
                    'body' => 'برای انتخاب بین دوره ویدئویی و دوره دارای کلاس آنلاین راهنمایی می‌خواهم.',
                    'status' => 'new',
                ]
            );

            SeoArticle::query()->firstOrCreate(
                ['slug' => 'how-to-choose-online-course'],
                [
                    'author_id' => $teacher->id,
                    'title' => 'چطور دوره آموزشی مناسب خود را انتخاب کنیم؟',
                    'excerpt' => 'تفاوت دوره ویدئویی خودآموز و دوره دارای کلاس آنلاین را بشناسید و بر اساس نیازتان انتخاب کنید.',
                    'body' => '<h2>دوره ویدئویی یا کلاس آنلاین؟</h2><p>در دوره ویدئویی، دانش‌آموز در زمان دلخواه محتوای ضبط‌شده را مشاهده می‌کند و الزاماً کلاس زمان‌بندی‌شده‌ای وجود ندارد.</p><p>در دوره دارای کلاس آنلاین، جلسه‌ها زمان مشخص دارند و حضور، برنامه هفتگی و تعامل زنده بخشی از تجربه آموزشی است.</p><h2>منابع و تکالیف</h2><p>جزوه و فایل PDF می‌توانند همراه دوره باشند، اما اجباری نیستند. تکلیف نیز می‌تواند فقط پاسخ متنی داشته باشد و در صورت نیاز فایل ضمیمه دریافت کند.</p>',
                    'featured_image_path' => null,
                    'image_position' => 'center',
                    'image_alt' => 'راهنمای انتخاب دوره آموزشی',
                    'meta_title' => 'راهنمای انتخاب دوره آموزشی | آکادمی شیخان',
                    'meta_description' => 'تفاوت دوره ویدئویی، کلاس آنلاین، منابع و تکالیف را بشناسید.',
                    'canonical_url' => null,
                    'og_image_path' => null,
                    'keywords' => ['دوره آموزشی', 'کلاس آنلاین', 'آموزش ویدئویی'],
                    'status' => 'published',
                    'published_at' => $now->copy()->subDays(2),
                ]
            );
        });
    }
}
