<?php

namespace App\\Services\\Learning;

use App\\Models\\Classroom;
use App\\Models\\User;
use App\\Services\\Skyroom\\SkyroomService;

/**
 * Application-facing live-class facade.
 *
 * Provider-specific HTTP details stay behind SkyroomGateway. The default
 * gateway reports a contract-pending state and never sends guessed requests.
 */
final class LiveClassService
{
    public function __construct(private readonly SkyroomService $skyroom) {}

    public function ensureRoom(Classroom $classroom): ?string
    {
        return $this->skyroom->ensureRoom($classroom);
    }

    public function joinUrl(User $user, Classroom $classroom): ?string
    {
        return $this->skyroom->joinUrl($user, $classroom);
    }
}
