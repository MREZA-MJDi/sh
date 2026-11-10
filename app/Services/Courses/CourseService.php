<?php

namespace App\Services\Courses;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\IntegrationRun;
use App\Services\Integrations\IntegrationOperationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseService
{
    public function __construct(
        private readonly IntegrationOperationService $operations,
        private readonly CourseEnrollmentService $enrollments,
    ) {}

    public function create(array $data): Course
    {
        return DB::transaction(function () use ($data): Course {
            $deliveryType = $data['delivery_type'];
            $externalId = $deliveryType === 'spotplayer' ? ($data['spotplayer_course_id'] ?? null) : null;

            $course = Course::create([
                ...$data,
                'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(7)),
                'price' => $data['type'] === 'paid' ? (int) $data['price'] : 0,
                'spotplayer_course_id' => $externalId,
                'integration_status' => $deliveryType === 'spotplayer'
                    ? (filled($externalId) ? 'awaiting_api_verification' : 'waiting_course_mapping')
                    : 'not_required',
                'integration_note' => $deliveryType === 'spotplayer'
                    ? 'دوره در سامانه محلی ساخته شد؛ اتصال SpotPlayer تا دریافت و پیاده‌سازی قرارداد API تأیید نشده است.'
                    : null,
            ]);

            if ($deliveryType === 'spotplayer') {
                $this->queueCourseMapping($course);
            }

            return $course;
        });
    }

    public function update(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data): Course {
            $deliveryType = $data['delivery_type'];
            $externalId = $deliveryType === 'spotplayer' ? ($data['spotplayer_course_id'] ?? null) : null;

            $course->update([
                ...$data,
                'price' => $data['type'] === 'paid' ? (int) $data['price'] : 0,
                'spotplayer_course_id' => $externalId,
                'integration_status' => $deliveryType === 'spotplayer'
                    ? (filled($externalId) ? 'awaiting_api_verification' : 'waiting_course_mapping')
                    : 'not_required',
                'integration_note' => $deliveryType === 'spotplayer'
                    ? 'شناسه خارجی ثبت شده یا منتظر ثبت است؛ اعتبار آن پس از فعال‌شدن API بررسی می‌شود.'
                    : null,
            ]);

            if ($deliveryType === 'spotplayer') {
                $this->queueCourseMapping($course);
            }

            $this->enrollments->syncCourseRoster($course);

            return $course->refresh();
        });
    }

    public function delete(Course $course): void
    {
        if (
            $course->classrooms()->exists()
            || $course->enrollments()->exists()
            || $course->spotPlayerLicenses()->exists()
            || $course->sections()->exists()
            || $course->resources()->exists()
            || $course->exams()->exists()
            || $course->spotPlayerVideos()->exists()
        ) {
            throw ValidationException::withMessages([
                'course' => 'این دوره دارای محتوای آموزشی، منابع، آزمون، کلاس، ثبت‌نام یا لایسنس است؛ برای حفظ سوابق آن را بایگانی کنید.',
            ]);
        }

        $course->delete();
    }

    private function queueCourseMapping(Course $course): IntegrationRun
    {
        $action = filled($course->spotplayer_course_id)
            ? 'verify_course_mapping'
            : 'create_course';

        return $this->operations->waitForContract(
            provider: 'spotplayer',
            action: $action,
            entity: $course,
            safeRequestMeta: [
                'local_course_id' => $course->id,
                'requested_external_course_id' => $course->spotplayer_course_id,
            ],
            idempotencyKey: 'spotplayer:course-map:'.$course->id.':'.sha1((string) $course->spotplayer_course_id),
        );
    }
}
