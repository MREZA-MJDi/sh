<?php

namespace App\Services\Quiz24\Contracts;

final readonly class Quiz24StudentLaunchData
{
    public function __construct(
        public string $launchUrl,
        public string $expiresAt,
        public ?string $providerSessionId = null,
    ) {}
}
