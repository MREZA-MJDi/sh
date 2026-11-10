<?php

namespace App\Services\Quiz24\Contracts;

final readonly class Quiz24ExamData
{
    public function __construct(
        public string $externalId,
        public string $title,
        public ?string $description = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        public ?string $status = null,
    ) {}
}
