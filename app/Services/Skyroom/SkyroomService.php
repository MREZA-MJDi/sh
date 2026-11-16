<?php

namespace App\Services\Skyroom;

use App\Models\Classroom;
use App\Models\User;
use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Integrations\IntegrationException;
use App\Services\Skyroom\Contracts\SkyroomGateway;
use App\Services\Skyroom\Contracts\SkyroomLoginCommand;
use App\Services\Skyroom\Contracts\SkyroomRoomCommand;
use DateTimeImmutable;
use InvalidArgumentException;

final class SkyroomService
{
    public function __construct(private readonly SkyroomGateway $gateway) {}

    public function health(): IntegrationHealth
    {
        return $this->gateway->health();
    }

    public function ensureRoom(Classroom $classroom): ?string
    {
        if (filled($classroom->skyroom_room_id)) {
            return (string) $classroom->skyroom_room_id;
        }

        $health = $this->gateway->health();
        if (! $health->available) {
            $classroom->forceFill([
                'skyroom_status' => 'waiting_contract',
                'skyroom_note' => $health->message,
            ])->save();

            return null;
        }

        try {
            $externalRoomId = trim($this->gateway->createRoom(new SkyroomRoomCommand(
                localClassroomId: (int) $classroom->id,
                name: 'shikhan-class-'.$classroom->id,
                title: $classroom->name,
            )));
        } catch (IntegrationException $exception) {
            $classroom->forceFill([
                'skyroom_status' => 'failed',
                'skyroom_note' => mb_substr($exception->getMessage(), 0, 500),
            ])->save();

            return null;
        }

        if ($externalRoomId === '') {
            throw new InvalidArgumentException('Skyroom returned an empty room identifier.');
        }

        $classroom->forceFill([
            'skyroom_room_id' => $externalRoomId,
            'skyroom_status' => 'ready',
            'skyroom_note' => null,
        ])->save();

        return $externalRoomId;
    }

    public function joinUrl(User $user, Classroom $classroom): ?string
    {
        if (blank($classroom->skyroom_room_id)) {
            return null;
        }

        $health = $this->gateway->health();
        if (! $health->available) {
            return null;
        }

        try {
            return $this->gateway->createLoginUrl(new SkyroomLoginCommand(
                externalRoomId: (string) $classroom->skyroom_room_id,
                externalUserId: 'student-'.$user->id,
                displayName: $user->name,
                language: 'fa',
                ttlMinutes: 15,
            ));
        } catch (IntegrationException $exception) {
            $classroom->forceFill([
                'skyroom_status' => 'failed',
                'skyroom_note' => mb_substr($exception->getMessage(), 0, 500),
            ])->save();

            return null;
        }
    }

    /** @return iterable<\App\Services\Skyroom\Contracts\SkyroomAttendanceEvent> */
    public function attendance(string $meetingId, DateTimeImmutable $from, DateTimeImmutable $to): iterable
    {
        if (trim($meetingId) === '' || $to <= $from) {
            throw new InvalidArgumentException('شناسه جلسه یا بازه زمانی Skyroom معتبر نیست.');
        }

        return $this->gateway->fetchAttendance($meetingId, $from, $to);
    }
}
