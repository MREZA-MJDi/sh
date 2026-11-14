<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\User;

/** تنها منبع تصمیم‌گیری درباره «این کاربر چه چیزی از این دوره می‌بیند». */
class CourseAccess
{
    public const GUEST = 'guest';
    public const NOT_ENROLLED = 'not_enrolled';
    public const PENDING_PAYMENT = 'pending_payment';
    public const LICENSE_PREPARING = 'license_preparing';
    public const LICENSE_WAITING = 'license_waiting';
    public const LICENSE_FAILED = 'license_failed';
    public const REVOKED = 'revoked';
    public const ACTIVE = 'active';

    /**
     * @return array{state: string, unlocked: bool, enrollment: ?Enrollment, license: ?\App\Models\CourseLicense}
     */
    public function state(?User $user, Course $course): array
    {
        $none = ['unlocked' => false, 'enrollment' => null, 'license' => null];

        if (! $user) {
            return ['state' => self::GUEST] + $none;
        }

        $enrollment = Enrollment::with('license')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            return ['state' => self::NOT_ENROLLED] + $none;
        }

        $license = $enrollment->license;
        $base = ['enrollment' => $enrollment, 'license' => $license];

        if ($enrollment->status === Enrollment::PENDING) {
            return ['state' => self::PENDING_PAYMENT, 'unlocked' => false] + $base;
        }

        if (! $enrollment->isActive()) {
            return ['state' => self::NOT_ENROLLED, 'unlocked' => false] + $base;
        }

        // ثبت‌نام فعال است → قفل درس‌ها باز؛ وضعیت لایسنس فقط پیام را تعیین می‌کند.
        if (! $course->needsLicense()) {
            return ['state' => self::ACTIVE, 'unlocked' => true] + $base;
        }

        $state = match ($license?->status) {
            'active' => self::ACTIVE,
            'failed' => self::LICENSE_FAILED,
            'waiting_contract' => self::LICENSE_WAITING,
            'revoked' => self::REVOKED,
            default => self::LICENSE_PREPARING,
        };

        return ['state' => $state, 'unlocked' => $state !== self::REVOKED] + $base;
    }

    public function canViewLesson(?User $user, Course $course, CourseLesson $lesson): bool
    {
        return $lesson->status === 'published' && $this->state($user, $course)['unlocked'];
    }
}
