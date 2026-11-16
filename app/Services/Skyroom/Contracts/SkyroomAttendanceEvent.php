<?php

namespace App\\Services\\Skyroom\\Contracts;

use DateTimeImmutable;

final readonly class SkyroomAttendanceEvent
{
    public function __construct(
        public string $externalParticipantId,
        public ?string $studentReference,
        public DateTimeImmutable $joinedAt,
        public ?DateTimeImmutable $leftAt,
        public int $attendedMinutes,
    ) {}
}
