<?php

namespace App\Services\SpotPlayer;

use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Integrations\IntegrationException;
use App\Services\SpotPlayer\Contracts\IssuedSpotPlayerLicense;
use App\Services\SpotPlayer\Contracts\SpotPlayerCourseCommand;
use App\Services\SpotPlayer\Contracts\SpotPlayerCourseData;
use App\Services\SpotPlayer\Contracts\SpotPlayerGateway;
use App\Services\SpotPlayer\Contracts\SpotPlayerLicenseCommand;
use App\Services\SpotPlayer\Contracts\SpotPlayerVideoCommand;
use App\Services\SpotPlayer\Contracts\SpotPlayerVideoData;

class UnavailableSpotPlayerGateway implements SpotPlayerGateway
{
    public function health(): IntegrationHealth
    {
        return new IntegrationHealth(
            false,
            'contract_missing',
            'قرارداد رسمی وب‌سرویس SpotPlayer هنوز از ارائه‌دهنده دریافت نشده است.'
        );
    }

    public function listCourses(): array
    {
        throw new IntegrationException('همگام‌سازی دوره‌ها تا دریافت مستندات و دسترسی API SpotPlayer غیرفعال است.');
    }

    public function createCourse(SpotPlayerCourseCommand $command): SpotPlayerCourseData
    {
        throw new IntegrationException('ساخت دوره خارجی تا دریافت قرارداد رسمی API SpotPlayer غیرفعال است.');
    }

    public function registerVideo(SpotPlayerVideoCommand $command): SpotPlayerVideoData
    {
        throw new IntegrationException('ثبت یا آپلود ویدیو تا دریافت قرارداد رسمی API SpotPlayer غیرفعال است.');
    }

    public function issueLicense(SpotPlayerLicenseCommand $command): IssuedSpotPlayerLicense
    {
        throw new IntegrationException('صدور واقعی لایسنس تا دریافت قرارداد API SpotPlayer غیرفعال است.');
    }

    public function revokeLicense(string $externalLicenseId, string $reason, string $idempotencyKey): void
    {
        throw new IntegrationException('ابطال واقعی لایسنس تا دریافت قرارداد API SpotPlayer غیرفعال است.');
    }
}
