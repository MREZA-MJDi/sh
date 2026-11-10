<?php

namespace App\Services\SpotPlayer\Contracts;

final readonly class SpotPlayerLicenseCommand
{
    /**
     * This is the canonical internal command. The future provider adapter maps
     * it to the exact request schema supplied by SpotPlayer.
     *
     * @param array<string, int> $deviceLimits
     */
    public function __construct(
        public string $idempotencyKey,
        public string $studentReference,
        public string $studentName,
        public string $externalCourseId,
        public ?string $watermarkText,
        public array $deviceLimits,
        public ?string $expiresAt = null,
    ) {}
}
