<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\LearningResource;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Message;
use App\Models\MessageReply;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\StudentNote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class AcademicPortalContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $teacherRole = Role::query()->where('name', 'teacher')->first();
            $studentRole = Role::query()->where('name', 'student')->first();
            $parentRole = Role::query()->where('name', 'parent')->first();

            if (! $teacherRole || ! $studentRole) {
                $this->command?->warn('محتوای آموزشی ساخته نشد؛ ابتدا نقش‌ها و حساب‌های واقعی مدرس و دانش‌آموز را ایجاد یا وارد کنید.');

                return;
            }

            $teacher = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->whereKey($teacherRole->id))
                ->orderBy('id')
                ->get()
                ->first();

            $students = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->whereKey($studentRole->id))
                ->orderBy('id')
                ->get();

            if (! $teacher || $students->isEmpty()) {
                $this->command?->warn('محتوای آموزشی ساخته نشد؛ حساب فعال مدرس و حداقل یک دانش‌آموز لازم است.');

                return;
            }

            $parent = $parentRole
                ? User::query()
                    ->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->whereKey($parentRole->id))
                    ->orderBy('id')
                    ->get()
                    ->first()
                : null;

            $course = Course::query()->updateOrCreate(
                ['slug' => 'riyazi-takmili-nomre'],
                [
                    'title' => 'ریاضی تکمیلی و حل مسئله',
                    'description' => 'دوره آموزشی حل مسئله، تقویت مهارت محاسباتی و آمادگی آزمون با تمرین‌های مرحله‌ای.',
                    'subject' => 'ریاضی',
                    'grade' => $students->first()->grade ?: 'هفتم',
                    'teacher_id' => $teacher->id,
                    'type' => 'free',
                    'price' => 0,
                    'delivery_type' => 'spotplayer',
                    'status' => 'published',
                    'integration_status' => 'not_required',
                    'integration_note' => null,
                ]
            );

            $classroom = Classroom::query()->updateOrCreate(
                ['course_id' => $course->id, 'name' => 'کلاس ریاضی تکمیلی - گروه اول'],
                [
                    'teacher_id' => $teacher->id,
                    'schedule' => [
                        ['day' => 'شنبه', 'start' => '16:00', 'end' => '17:30', 'label' => 'جلسه حل مسئله'],
                        ['day' => 'دوشنبه', 'start' => '16:00', 'end' => '17:30', 'label' => 'تمرین و رفع اشکال'],
                    ],
                    'meeting_url' => null,
                    'status' => 'active',
                    'created_by' => $teacher->id,
                ]
            );

            $sectionDefinitions = [
                ['title' => 'مبانی حل مسئله', 'description' => 'روش خواندن سؤال، تشخیص داده‌ها و انتخاب راهبرد.', 'lessons' => [
                    ['title' => 'شناخت مسئله و داده‌های آن', 'duration' => 35],
                    ['title' => 'الگوها و راهبردهای حل مسئله', 'duration' => 45],
                ]],
                ['title' => 'تمرین‌های تکمیلی', 'description' => 'تمرین‌های سطح‌بندی‌شده و تحلیل پاسخ.', 'lessons' => [
                    ['title' => 'تمرین‌های چندمرحله‌ای', 'duration' => 40],
                    ['title' => 'تحلیل خطا و روش‌های جایگزین', 'duration' => 30],
                ]],
            ];

            $lessons = collect();
            foreach ($sectionDefinitions as $sectionIndex => $definition) {
                $section = CourseSection::query()->updateOrCreate(
                    ['course_id' => $course->id, 'title' => $definition['title']],
                    [
                        'description' => $definition['description'],
                        'sort_order' => $sectionIndex + 1,
                        'status' => 'published',
                    ]
                );

                foreach ($definition['lessons'] as $lessonIndex => $lessonData) {
                    $lesson = Lesson::query()->updateOrCreate(
                        ['course_section_id' => $section->id, 'title' => $lessonData['title']],
                        [
                            'body' => 'در این درس، مفهوم اصلی با مثال آموزشی توضیح داده می‌شود و در پایان تمرین مرتبط ارائه خواهد شد.',
                            'duration_minutes' => $lessonData['duration'],
                            'sort_order' => $lessonIndex + 1,
                            'status' => 'published',
                        ]
                    );
                    $lessons->push($lesson);
                }
            }

            $today = Carbon::today();
            foreach ($students as $studentIndex => $student) {
                $classroom->students()->syncWithoutDetaching([
                    $student->id => ['assigned_by' => $teacher->id],
                ]);

                CourseEnrollment::query()->updateOrCreate(
                    ['course_id' => $course->id, 'student_id' => $student->id],
                    [
                        'source' => 'classroom',
                        'status' => 'active',
                        'granted_by' => $teacher->id,
                        'source_reference' => 'academic-portal-content',
                        'granted_at' => $today->copy()->subDays(30),
                        'expires_at' => null,
                    ]
                );

                foreach ([21, 14, 7, 2] as $sessionIndex => $daysAgo) {
                    $sessionDate = $today->copy()->subDays($daysAgo);
                    $statuses = ['present', 'present', 'late', 'absent'];
                    Attendance::query()->updateOrCreate(
                        [
                            'classroom_id' => $classroom->id,
                            'student_id' => $student->id,
                            'session_date' => $sessionDate->toDateString(),
                        ],
                        [
                            'status' => $statuses[($sessionIndex + $studentIndex) % count($statuses)],
                            'joined_at' => $sessionIndex === 2
                                ? $sessionDate->copy()->setTime(16, 12)
                                : ($sessionIndex === 3 ? null : $sessionDate->copy()->setTime(16, 0)),
                            'note' => $sessionIndex === 2 ? 'ورود با تأخیر ثبت‌شده' : null,
                            'marked_by' => $teacher->id,
                        ]
                    );
                }

                foreach ($lessons as $lessonIndex => $lesson) {
                    LessonProgress::query()->updateOrCreate(
                        ['lesson_id' => $lesson->id, 'student_id' => $student->id],
                        [
                            'progress_percent' => $lessonIndex < (2 + ($studentIndex % 2)) ? 100 : 0,
                            'started_at' => $lessonIndex < (2 + ($studentIndex % 2)) ? $today->copy()->subDays(12) : null,
                            'last_viewed_at' => $lessonIndex < (2 + ($studentIndex % 2)) ? $today->copy()->subDays(2) : null,
                            'completed_at' => $lessonIndex < (2 + ($studentIndex % 2)) ? $today->copy()->subDays(2) : null,
                        ]
                    );
                }
            }

            $homeworkDefinitions = [
                ['title' => 'تمرین راهبردهای حل مسئله', 'due' => 5, 'instructions' => 'سه مسئله از مبحث الگوها را با نوشتن تمام مراحل حل کنید.'],
                ['title' => 'کاربرگ محاسبات تکمیلی', 'due' => -2, 'instructions' => 'پاسخ تمرین‌های کاربرگ را همراه با روش محاسبه ثبت کنید.'],
            ];

            $homeworks = collect();
            foreach ($homeworkDefinitions as $definition) {
                $homeworks->push(Homework::query()->updateOrCreate(
                    ['classroom_id' => $classroom->id, 'title' => $definition['title']],
                    [
                        'instructions' => $definition['instructions'],
                        'due_at' => $today->copy()->addDays($definition['due'])->setTime(23, 59),
                        'attachment_path' => null,
                        'status' => 'published',
                        'created_by' => $teacher->id,
                    ]
                ));
            }

            foreach ($students as $studentIndex => $student) {
                $completedHomework = $homeworks->first();
                HomeworkSubmission::query()->updateOrCreate(
                    ['homework_id' => $completedHomework->id, 'student_id' => $student->id],
                    [
                        'answer' => 'پاسخ تمرین همراه با مراحل حل و توضیح روش انتخاب‌شده ثبت شده است.',
                        'attachment_path' => null,
                        'score' => 17 + ($studentIndex % 4),
                        'feedback' => 'مراحل حل روشن است؛ در نوشتن نتیجه نهایی دقت بیشتری داشته باشید.',
                        'status' => 'reviewed',
                        'submitted_at' => $today->copy()->subDays(3),
                        'reviewed_by' => $teacher->id,
                        'reviewed_at' => $today->copy()->subDays(2),
                    ]
                );
            }

            $exam = Exam::query()->updateOrCreate(
                ['course_id' => $course->id, 'title' => 'آزمونک فصل اول ریاضی'],
                [
                    'classroom_id' => $classroom->id,
                    'created_by' => $teacher->id,
                    'description' => 'ارزیابی مفاهیم پایه و راهبردهای حل مسئله فصل اول.',
                    'provider' => 'internal',
                    'external_id' => null,
                    'starts_at' => $today->copy()->subDays(1)->setTime(10, 0),
                    'duration_minutes' => 30,
                    'show_to_guardians' => true,
                    'status' => 'published',
                    'integration_status' => 'not_required',
                    'integration_note' => null,
                ]
            );

            foreach ($students as $studentIndex => $student) {
                ExamResult::query()->updateOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $student->id],
                    [
                        'score' => 15 + ($studentIndex % 6),
                        'max_score' => 20,
                        'source_data' => ['source' => 'internal_assessment', 'seed_key' => 'academic-portal-content'],
                        'synced_at' => $today->copy()->subDay(),
                    ]
                );
            }

            LearningResource::query()->updateOrCreate(
                ['course_id' => $course->id, 'classroom_id' => $classroom->id, 'title' => 'جزوه راهبردهای حل مسئله'],
                [
                    'uploaded_by' => $teacher->id,
                    'description' => 'سرفصل‌ها و نکته‌های کلیدی دوره برای مرور دانش‌آموزان.',
                    'kind' => 'document',
                    'price' => 0,
                    'is_free' => true,
                    'visibility' => 'enrolled',
                    'file_path' => null,
                    'status' => 'published',
                ]
            );

            SpotPlayerVideo::query()->updateOrCreate(
                ['course_id' => $course->id, 'classroom_id' => $classroom->id, 'title' => 'معرفی دوره و مسیر یادگیری'],
                [
                    'created_by' => $teacher->id,
                    'external_id' => null,
                    'embed_url' => null,
                    'status' => 'draft',
                ]
            );

            Announcement::query()->updateOrCreate(
                ['title' => 'آغاز برنامه آموزشی دوره ریاضی تکمیلی'],
                [
                    'created_by' => $teacher->id,
                    'body' => 'برنامه جلسات، تمرین‌ها و ارزیابی‌های دوره در پنل آموزشی در دسترس قرار گرفته است. زمان‌بندی هر جلسه را از بخش کلاس‌ها دنبال کنید.',
                    'audience' => 'all',
                    'status' => 'published',
                    'published_at' => now()->subDays(10),
                ]
            );

            foreach ($students as $studentIndex => $student) {
                StudentNote::query()->updateOrCreate(
                    ['student_id' => $student->id, 'title' => 'یادداشت برنامه‌ریزی هفتگی'],
                    [
                        'body' => 'مرور درس‌های تکمیل‌شده و حل تمرین‌های باقی‌مانده در برنامه این هفته قرار دارد.',
                        'tone' => $studentIndex % 2 === 0 ? 'blue' : 'green',
                    ]
                );

                if ($parent) {
                    $parent->children()->syncWithoutDetaching([$student->id]);
                }

                Message::query()->updateOrCreate(
                    [
                        'sender_id' => $student->id,
                        'recipient_id' => $teacher->id,
                        'student_id' => $student->id,
                        'subject' => 'پرسش درباره تمرین‌های جلسه',
                    ],
                    [
                        'parent_id' => null,
                        'body' => 'برای حل تمرین‌های چندمرحله‌ای، آیا لازم است تمام راه‌حل‌های جایگزین را هم بنویسیم؟',
                        'status' => 'replied',
                        'replied_at' => now()->subDays(1),
                    ]
                );

                $message = Message::query()
                    ->where('sender_id', $student->id)
                    ->where('recipient_id', $teacher->id)
                    ->where('student_id', $student->id)
                    ->where('subject', 'پرسش درباره تمرین‌های جلسه')
                    ->get()
                    ->first();

                if ($message) {
                    MessageReply::query()->updateOrCreate(
                        ['message_id' => $message->id, 'sender_id' => $teacher->id],
                        ['body' => 'نوشتن روش اصلی و توضیح دلیل انتخاب آن کافی است؛ روش جایگزین امتیاز تکمیلی دارد.']
                    );
                }

                if ($parent) {
                    Consultation::query()->updateOrCreate(
                        [
                            'requester_id' => $parent->id,
                            'student_id' => $student->id,
                            'topic' => 'گزارش پیشرفت تحصیلی',
                        ],
                        [
                            'teacher_id' => $teacher->id,
                            'handled_by' => $teacher->id,
                            'type' => 'phone',
                            'contact_phone' => $parent->phone,
                            'desired_at' => $today->copy()->addDays(2)->setTime(17, 0),
                            'scheduled_at' => null,
                            'status' => 'pending',
                            'notes' => 'درخواست بررسی روند پیشرفت و برنامه تمرین دانش‌آموز.',
                        ]
                    );
                }
            }
        });
    }
}
