<?php

namespace App\Services\SpotPlayer\Contracts;

final readonly class IssuedSpotPlayerLicense
{
    public function __construct(
        public string $externalId,
        public string $licenseKey,
        public string $secureUrl,
        public ?string $issuedAt = null,
    ) {}
}
