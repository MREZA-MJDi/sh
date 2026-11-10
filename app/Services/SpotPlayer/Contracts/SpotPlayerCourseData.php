<?php

namespace App\Services\SpotPlayer\Contracts;

final readonly class SpotPlayerCourseData
{
    public function __construct(
        public string $externalCourseId,
        public string $title,
        public ?string $status = null,
    ) {}
}
