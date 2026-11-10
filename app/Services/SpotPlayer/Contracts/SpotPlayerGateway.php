<?php

namespace App\Services\SpotPlayer\Contracts;

use App\Services\Integrations\Contracts\IntegrationHealth;

interface SpotPlayerGateway
{
    public function health(): IntegrationHealth;

    /** @return array<int, SpotPlayerCourseData> */
    public function listCourses(): array;

    public function createCourse(SpotPlayerCourseCommand $command): SpotPlayerCourseData;

    public function registerVideo(SpotPlayerVideoCommand $command): SpotPlayerVideoData;

    public function issueLicense(SpotPlayerLicenseCommand $command): IssuedSpotPlayerLicense;

    public function revokeLicense(string $externalLicenseId, string $reason, string $idempotencyKey): void;
}
