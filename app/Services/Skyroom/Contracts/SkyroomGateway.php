<?php

namespace App\\Services\\Skyroom\\Contracts;

use App\\Services\\Integrations\\Contracts\\IntegrationHealth;
use DateTimeImmutable;

interface SkyroomGateway
{
    public function health(): IntegrationHealth;

    public function createRoom(SkyroomRoomCommand $command): string;

    public function createLoginUrl(SkyroomLoginCommand $command): string;

    /** @return iterable<SkyroomAttendanceEvent> */
    public function fetchAttendance(string $externalMeetingId, DateTimeImmutable $from, DateTimeImmutable $to): iterable;
}
