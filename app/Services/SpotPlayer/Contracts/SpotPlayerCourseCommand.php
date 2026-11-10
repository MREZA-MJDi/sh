<?php

namespace App\Services\SpotPlayer\Contracts;

final readonly class SpotPlayerCourseCommand
{
    public function __construct(
        public string $idempotencyKey,
        public int $localCourseId,
        public string $title,
        public ?string $description,
        public ?string $subject,
        public ?string $grade,
    ) {}
}
