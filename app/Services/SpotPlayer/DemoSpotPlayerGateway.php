<?php

namespace App\Services\SpotPlayer;

use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\SpotPlayer\Contracts\IssuedSpotPlayerLicense;
use App\Services\SpotPlayer\Contracts\SpotPlayerCourseCommand;
use App\Services\SpotPlayer\Contracts\SpotPlayerCourseData;
use App\Services\SpotPlayer\Contracts\SpotPlayerGateway;
use App\Services\SpotPlayer\Contracts\SpotPlayerLicenseCommand;
use App\Services\SpotPlayer\Contracts\SpotPlayerVideoCommand;
use App\Services\SpotPlayer\Contracts\SpotPlayerVideoData;

/** شبیه‌ساز نمایشی؛ هیچ درخواست بیرونی نمی‌فرستد. */
class DemoSpotPlayerGateway implements SpotPlayerGateway
{
    public function health(): IntegrationHealth
    {
        return new IntegrationHealth(true, 'demo', 'حالت نمایشی فعال است؛ اتصال واقعی SpotPlayer برقرار نیست.', 'demo');
    }

    public function listCourses(): array
    {
        return [new SpotPlayerCourseData('demo-course-1', 'دوره نمایشی', 'active')];
    }

    public function createCourse(SpotPlayerCourseCommand $command): SpotPlayerCourseData
    {
        return new SpotPlayerCourseData('demo-course-'.$command->localCourseId, $command->title, 'active');
    }

    public function registerVideo(SpotPlayerVideoCommand $command): SpotPlayerVideoData
    {
        return new SpotPlayerVideoData('demo-video-'.$command->localVideoId, 'active', 'demo');
    }

    public function issueLicense(SpotPlayerLicenseCommand $command): IssuedSpotPlayerLicense
    {
        $id = 'demo-lic-'.substr(sha1($command->idempotencyKey), 0, 10);

        return new IssuedSpotPlayerLicense($id, strtoupper($id), url('/'), now()->toIso8601String());
    }

    public function revokeLicense(string $externalLicenseId, string $reason, string $idempotencyKey): void {}
}
