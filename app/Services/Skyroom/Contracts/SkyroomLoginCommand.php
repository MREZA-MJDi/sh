<?php

namespace App\Services\Skyroom\Contracts;

final readonly class SkyroomLoginCommand
{
    public function __construct(
        public string $externalRoomId,
        public string $externalUserId,
        public string $displayName,
        public string $language = 'fa',
        public int $ttlMinutes = 120,
    ) {}
}
