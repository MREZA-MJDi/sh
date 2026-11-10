<?php

namespace App\Services\Integrations\Contracts;

final readonly class IntegrationHealth
{
    public function __construct(
        public bool $available,
        public string $state,
        public string $message,
        public ?string $providerVersion = null,
    ) {}
}
