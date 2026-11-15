<?php

namespace App\Jobs\Learning;

use App\Exceptions\ContractPendingException;
use App\Models\CourseLicense;
use App\Services\Learning\LearningProvider;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IssueSpotPlayerLicense implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 60;
    public array $backoff = [30, 120, 600, 1800];
    public int $uniqueFor = 600;

    public function __construct(public int $licenseId)
    {
        $this->onQueue('licenses');
    }

    public function uniqueId(): string
    {
        return (string) $this->licenseId;
    }

    public function handle(LearningProvider $provider): void
    {
        $license = CourseLicense::with('enrollment.user', 'enrollment.course')->find($this->licenseId);

        if (! $license || $license->status === CourseLicense::ACTIVE || $license->status === CourseLicense::REVOKED) {
            return;
        }

        $enrollment = $license->enrollment;
        if (! $enrollment->isActive() || ! $enrollment->course->needsLicense()) {
            return; // شرایط صدور برقرار نیست
        }

        $user = $enrollment->user;

        try {
            $issued = $provider->issueLicense(
                (string) $enrollment->course->external_course_id,
                (string) ($user->name ?? "user-{$user->id}"),
                (string) ($user->phone ?? $user->email ?? $user->id) // متن واترمارک
            );
        } catch (ContractPendingException $e) {
            // retry بی‌فایده است؛ تا تحویل API در این وضعیت می‌ماند و ادمین بعداً retry می‌زند.
            $license->update(['status' => CourseLicense::WAITING_CONTRACT, 'last_error' => $e->getMessage()]);
            return;
        } catch (Throwable $e) {
            $license->update(['attempts' => $license->attempts + 1, 'last_error' => mb_substr($e->getMessage(), 0, 500)]);
            throw $e; // retry با backoff
        }

        $license->update([
            'status' => CourseLicense::ACTIVE,
            'external_license_id' => $issued['id'],
            'license_key' => $issued['key'],
            'issued_at' => now(),
            'last_error' => null,
        ]);
    }

    public function failed(Throwable $e): void
    {
        CourseLicense::whereKey($this->licenseId)
            ->whereNotIn('status', [CourseLicense::ACTIVE, CourseLicense::REVOKED])
            ->update(['status' => CourseLicense::FAILED, 'last_error' => mb_substr($e->getMessage(), 0, 500)]);
    }
}
