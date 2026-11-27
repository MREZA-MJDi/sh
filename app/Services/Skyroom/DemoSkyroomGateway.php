<?php

namespace App\Services\Skyroom;

use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Skyroom\Contracts\SkyroomGateway;
use App\Services\Skyroom\Contracts\SkyroomLoginCommand;
use App\Services\Skyroom\Contracts\SkyroomRoomCommand;
use DateTimeImmutable;

/** شبیه‌ساز نمایشی؛ هیچ درخواست بیرونی نمی‌فرستد. */
class DemoSkyroomGateway implements SkyroomGateway
{
    public function health(): IntegrationHealth
    {
        return new IntegrationHealth(true, 'demo', 'حالت نمایشی فعال است؛ اتصال واقعی Skyroom برقرار نیست.', 'demo');
    }

    public function createRoom(SkyroomRoomCommand $command): string
    {
        return 'demo-room-'.$command->localClassroomId;
    }

    public function createLoginUrl(SkyroomLoginCommand $command): string
    {
        return url('/demo/skyroom/'.rawurlencode($command->externalRoomId));
    }

    public function fetchAttendance(string $externalMeetingId, DateTimeImmutable $from, DateTimeImmutable $to): iterable
    {
        return [];
    }
}
