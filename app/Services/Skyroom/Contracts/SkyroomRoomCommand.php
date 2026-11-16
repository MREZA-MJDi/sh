<?php

namespace App\Services\Skyroom\Contracts;

final readonly class SkyroomRoomCommand
{
    public function __construct(
        public int $localClassroomId,
        public string $name,
        public string $title,
    ) {}
}
