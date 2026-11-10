<?php

namespace App\Services\Quiz24\Contracts;

final readonly class Quiz24ResultData
{
    public function __construct(
        public string $resultId,
        public string $studentIdentifier,
        public ?string $studentName,
        public float $score,
        public float $maxScore,
        public ?string $submittedAt = null,
    ) {}
}
