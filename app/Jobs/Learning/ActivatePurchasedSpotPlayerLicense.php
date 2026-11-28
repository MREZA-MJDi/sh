<?php

namespace App\Jobs\Learning;

use App\Models\CourseEnrollment;
use App\Models\SpotPlayerSetting;
use App\Services\SpotPlayer\SpotPlayerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;

final class ActivatePurchasedSpotPlayerLicense implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 1;
    public int $uniqueFor = 120;

    public function __construct(public int $enrollmentId) {}

    public function uniqueId(): string
    {
        return (string) $this->enrollmentId;
    }

    public function handle(SpotPlayerService $spotPlayer): void
    {
        $settings = SpotPlayerSetting::query()->first();
        if (! $settings?->api_enabled || blank($settings->api_key)) {
            return;
        }

        $enrollment = CourseEnrollment::query()->with(['student', 'course'])
            ->where('source', 'payment')->currentlyActive()->find($this->enrollmentId);

        if ($enrollment?->student?->is_active && $enrollment->student->hasRole('student')
            && $enrollment->course && filled($enrollment->course->spotplayer_course_id)
            && $enrollment->course->status === 'published' && $enrollment->course->featureEnabled('recorded_video')) {
            $spotPlayer->requestLicense($enrollment->student, $enrollment->course, $enrollment->student);
        }
    }
}
