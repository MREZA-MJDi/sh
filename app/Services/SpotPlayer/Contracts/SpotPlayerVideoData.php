<?php

namespace App\Services\SpotPlayer\Contracts;

final readonly class SpotPlayerVideoData
{
    public function __construct(
        public string $externalVideoId,
        public string $status,
        public ?string $processingReference = null,
    ) {}
}
