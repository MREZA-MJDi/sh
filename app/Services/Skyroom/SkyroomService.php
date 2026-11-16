<?php

namespace App\Services\Skyroom;

use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Skyroom\Contracts\SkyroomGateway;
use DateTimeImmutable;
use InvalidArgumentException;

final class SkyroomService
{
    public function __construct(private readonly SkyroomGateway $gateway) {}

    public function health(): IntegrationHealth
    {
        return $this->gateway->health();
    }

    /** @return iterable<\App\Services\Skyroom\Contracts\SkyroomAttendanceEvent> */
    public function attendance(string $meetingId, DateTimeImmutable $from, DateTimeImmutable $to): iterable
    {
        if (trim($meetingId) === '' || $to <= $from) {
            throw new InvalidArgumentException('شناسه جلسه یا بازه زمانی Skyroom معتبر نیست.');
        }

        return $this->gateway->fetchAttendance($meetingId, $from, $to);
    }
}
