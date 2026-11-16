<?php

namespace App\Services\Skyroom\Contracts;

use App\Services\Integrations\Contracts\IntegrationHealth;
use DateTimeImmutable;

interface SkyroomGateway
{
    public function health(): IntegrationHealth;

    /**
     * Return normalized attendance events for one provider meeting.
     *
     * @return iterable<SkyroomAttendanceEvent>
     */
    public function fetchAttendance(
        string $externalMeetingId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): iterable;
}
