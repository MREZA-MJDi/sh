<?php

namespace App\Services\Quiz24\Contracts;

final readonly class Quiz24StudentLaunchCommand
{
    public function __construct(
        public string $idempotencyKey,
        public int $localStudentId,
        public string $studentExternalReference,
        public string $externalExamId,
        public string $returnUrl,
    ) {}
}
