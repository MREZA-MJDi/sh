<?php

namespace App\Services\Learning;

use App\Jobs\Learning\IssueSpotPlayerLicense;
use App\Models\Course;
use App\Models\CourseLicense;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnrollmentService
{
    /** دوره رایگان منتشرشده → ثبت‌نام فعال (idempotent). */
    public function enrollFree(User $user, Course $course): Enrollment
    {
        if (! $course->isFree()) {
            throw new RuntimeException('این دوره رایگان نیست.');
        }

        return DB::transaction(function () use ($user, $course) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first()
                ?? Enrollment::create([
                    'user_id' => $user->id, 'course_id' => $course->id,
                    'status' => Enrollment::ACTIVE, 'source' => 'free', 'amount' => 0, 'paid_at' => now(),
                ]);

            if (! $enrollment->isActive() && $enrollment->status !== Enrollment::REFUNDED) {
                $enrollment->update(['status' => Enrollment::ACTIVE, 'source' => 'free', 'paid_at' => now()]);
            }

            $this->queueLicense($enrollment, $course);

            return $enrollment;
        });
    }

    /** دوره پولی: فقط Enrollment در حالت pending می‌سازد؛ فعال‌سازی فقط با Callback معتبر. */
    public function startPaid(User $user, Course $course): Enrollment
    {
        if ($course->isFree()) {
            throw new RuntimeException('این دوره رایگان است.');
        }

        return Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['status' => Enrollment::PENDING, 'source' => 'payment', 'amount' => (int) $course->price]
        );
    }

    /**
     * از داخل Callback پرداخت، بعد از verify موفق درگاه صدا بزن.
     * چند بار صدا زدن (رفرش صفحه/تکرار Callback) خطری ندارد.
     */
    public function activateAfterPayment(Enrollment $enrollment, int $verifiedAmount): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $verifiedAmount) {
            $e = Enrollment::with('course')->lockForUpdate()->findOrFail($enrollment->id);

            if ($e->isActive()) {
                return $e;
            }

            if ($verifiedAmount < (int) $e->course->price) {
                throw new RuntimeException('مبلغ تأییدشده با قیمت دوره نمی‌خواند.');
            }

            $e->update(['status' => Enrollment::ACTIVE, 'paid_at' => now(), 'amount' => $verifiedAmount]);
            $this->queueLicense($e, $e->course);

            return $e;
        });
    }

    public function queueLicense(Enrollment $enrollment, Course $course): void
    {
        if (! $course->needsLicense()) {
            return;
        }

        $license = CourseLicense::firstOrCreate(
            ['enrollment_id' => $enrollment->id],
            ['status' => CourseLicense::QUEUED]
        );

        if ($license->status === CourseLicense::ACTIVE) {
            return;
        }

        IssueSpotPlayerLicense::dispatch($license->id)->afterCommit();
    }
}
