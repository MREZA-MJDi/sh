<?php

namespace App\Services\SpotPlayer;

use App\Models\Course;
use App\Models\IntegrationRun;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerSetting;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Integrations\IntegrationException;
use App\Services\Integrations\IntegrationOperationService;
use App\Services\SpotPlayer\Contracts\SpotPlayerGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpotPlayerService
{
    public function __construct(
        private readonly SpotPlayerGateway $gateway,
        private readonly IntegrationOperationService $operations,
        private readonly CourseEnrollmentService $enrollments,
    ) {}

    public function health(): IntegrationHealth
    {
        return $this->gateway->health();
    }

    public function requestCourseCatalogSync(): IntegrationRun
    {
        return $this->operations->waitForContract(
            provider: 'spotplayer',
            action: 'sync_course_catalog',
            safeRequestMeta: ['requested_from' => 'admin'],
            idempotencyKey: 'spotplayer:course-catalog:'.now()->format('Ymd'),
        );
    }

    /**
     * Creates a local, idempotent request. It does not claim a license was
     * issued: the provider adapter must process this operation after contract delivery.
     */
    public function requestLicense(User $student, Course $course, User $requestedBy): SpotPlayerLicense
    {
        if (! $student->is_active || ! $student->hasRole('student')) {
            throw new IntegrationException('صدور لایسنس فقط برای دانش‌آموز فعال مجاز است.');
        }

        if ($course->status !== 'published' || $course->delivery_type !== 'spotplayer') {
            throw new IntegrationException('دوره باید منتشرشده و از نوع ویدیوی SpotPlayer باشد.');
        }

        if (blank($course->spotplayer_course_id)) {
            throw new IntegrationException('ابتدا شناسه دوره متناظر در پنل SpotPlayer را ثبت کنید.');
        }

        if (! $this->enrollments->hasActiveAccess($student, $course)) {
            throw new IntegrationException('دانش‌آموز برای این دوره دسترسی فعال ندارد؛ ابتدا ثبت‌نام یا پرداخت را نهایی کنید.');
        }

        $settings = SpotPlayerSetting::query()->first();

        if (($settings?->watermark_enabled ?? true) && blank($student->phone)) {
            throw new IntegrationException('برای واترمارک لایسنس، شماره موبایل دانش‌آموز لازم است.');
        }

        return DB::transaction(function () use ($student, $course, $requestedBy, $settings): SpotPlayerLicense {
            $license = SpotPlayerLicense::query()
                ->where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first();

            if ($license && in_array($license->status, ['active', 'waiting_contract', 'queued'], true)) {
                return $license;
            }

            $license ??= new SpotPlayerLicense([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);

            $license->fill([
                'max_devices' => $settings?->max_devices ?? 2,
                'status' => 'waiting_contract',
                'license_key' => null,
                'download_url' => null,
                'external_license_id' => null,
                'error_message' => 'وب‌سرویس رسمی SpotPlayer هنوز تحویل یا فعال نشده است.',
                'revoked_at' => null,
                'issued_at' => null,
            ])->save();

            $this->operations->waitForContract(
                provider: 'spotplayer',
                action: 'issue_license',
                entity: $license,
                safeRequestMeta: [
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'spotplayer_course_id' => $course->spotplayer_course_id,
                    'requested_by' => $requestedBy->id,
                    'watermark_enabled' => (bool) ($settings?->watermark_enabled ?? true),
                    'device_limit' => (int) ($settings?->max_devices ?? 2),
                ],
                idempotencyKey: 'spotplayer:issue-license:'.$license->id.':'.Str::uuid(),
            );

            return $license->refresh();
        });
    }

    public function requestRevocation(SpotPlayerLicense $license, User $requestedBy, string $reason): IntegrationRun
    {
        if ($license->status !== 'active' || blank($license->external_license_id)) {
            throw new IntegrationException('فقط لایسنس فعال و دارای شناسه خارجی قابل درخواست ابطال است.');
        }

        return DB::transaction(function () use ($license, $requestedBy, $reason): IntegrationRun {
            $run = $this->operations->waitForContract(
                provider: 'spotplayer',
                action: 'revoke_license',
                entity: $license,
                safeRequestMeta: [
                    'external_license_id' => $license->external_license_id,
                    'requested_by' => $requestedBy->id,
                    'reason' => Str::limit($reason, 300),
                ],
                idempotencyKey: 'spotplayer:revoke-license:'.$license->id.':'.sha1($reason),
            );

            $license->update([
                'error_message' => 'درخواست ابطال ثبت شد؛ وضعیت خارجی تا پاسخ رسمی سرویس همچنان فعال است.',
            ]);

            return $run;
        });
    }
}
