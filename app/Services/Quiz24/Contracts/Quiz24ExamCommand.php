<?php

namespace App\Services\Quiz24\Contracts;

final readonly class Quiz24ExamCommand
{
    public function __construct(
        public string $idempotencyKey,
        public int $localExamId,
        public string $title,
        public ?string $description,
        public ?string $startsAt,
        public ?string $endsAt,
        public ?string $grade,
        public ?string $subject,
    ) {}
}
