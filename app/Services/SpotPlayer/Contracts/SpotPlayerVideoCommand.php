<?php

namespace App\Services\SpotPlayer\Contracts;

final readonly class SpotPlayerVideoCommand
{
    public function __construct(
        public string $idempotencyKey,
        public int $localVideoId,
        public string $externalCourseId,
        public string $title,
        public string $storageReference,
        public string $mimeType,
        public int $fileSizeBytes,
    ) {}
}
