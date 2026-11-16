<?php

namespace App\Services\Skyroom;

use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Integrations\IntegrationException;
use App\Services\Skyroom\Contracts\SkyroomAttendanceEvent;
use App\Services\Skyroom\Contracts\SkyroomGateway;
use App\Services\Skyroom\Contracts\SkyroomLoginCommand;
use App\Services\Skyroom\Contracts\SkyroomRoomCommand;
use DateTimeImmutable;

final class UnavailableSkyroomGateway implements SkyroomGateway
{
    public function health(): IntegrationHealth
    {
        return new IntegrationHealth(
            false,
            'contract_missing',
            'مستندات رسمی API و دسترسی Sandbox اسکای‌روم هنوز ثبت نشده است.',
        );
    }

    public function createRoom(SkyroomRoomCommand $command): string
    {
        throw new IntegrationException('ساخت اتاق آنلاین تا تحویل و تأیید قرارداد رسمی Skyroom فعال نیست.');
    }

    public function createLoginUrl(SkyroomLoginCommand $command): string
    {
        throw new IntegrationException('تولید لینک ورود یکپارچه Skyroom تا تحویل قرارداد رسمی فعال نیست.');
    }

    public function fetchAttendance(string $externalMeetingId, DateTimeImmutable $from, DateTimeImmutable $to): iterable
    {
        throw new IntegrationException('دریافت خودکار حضور Skyroom تا تحویل قرارداد رسمی API فعال نیست.');
    }
}
