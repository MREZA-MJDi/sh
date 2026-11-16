<?php

namespace App\Services\Skyroom;

use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Integrations\IntegrationException;
use App\Services\Skyroom\Contracts\SkyroomAttendanceEvent;
use App\Services\Skyroom\Contracts\SkyroomGateway;
use DateTimeImmutable;

final class UnavailableSkyroomGateway implements SkyroomGateway
{
    public function health(): IntegrationHealth
    {
        return new IntegrationHealth(
            false,
            'contract_missing',
            'مستندات و دسترسی وب‌سرویس Skyroom هنوز از ارائه‌دهنده دریافت نشده است.',
        );
    }

    public function fetchAttendance(
        string $externalMeetingId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): iterable {
        throw new IntegrationException(
            'دریافت خودکار گزارش حضور Skyroom تا تحویل قرارداد رسمی API غیرفعال است.',
        );
    }
}
