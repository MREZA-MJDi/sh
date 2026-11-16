<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use App\Services\Learning\LiveClassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SkyroomContractFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_skyroom_contract_is_a_persisted_non_error_state(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس آنلاین آزمایشی',
            'status' => 'active',
        ]);

        $roomId = app(LiveClassService::class)->ensureRoom($classroom);

        $this->assertNull($roomId);
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'skyroom_room_id' => null,
            'skyroom_status' => 'waiting_contract',
        ]);
        $this->assertNotEmpty($classroom->fresh()->skyroom_note);
    }
    public function test_provider_failure_is_recorded_without_throwing_a_server_error(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس با خطای سرویس',
            'status' => 'active',
        ]);

        $this->app->instance(
            \\App\\Services\\Skyroom\\Contracts\\SkyroomGateway::class,
            new class implements \\App\\Services\\Skyroom\\Contracts\\SkyroomGateway {
                public function health(): \\App\\Services\\Integrations\\Contracts\\IntegrationHealth
                {
                    return new \\App\\Services\\Integrations\\Contracts\\IntegrationHealth(true, 'ready', 'متصل');
                }

                public function createRoom(\\App\\Services\\Skyroom\\Contracts\\SkyroomRoomCommand $command): string
                {
                    throw new \\App\\Services\\Integrations\\IntegrationException('Provider timeout');
                }

                public function createLoginUrl(\\App\\Services\\Skyroom\\Contracts\\SkyroomLoginCommand $command): string
                {
                    throw new \\App\\Services\\Integrations\\IntegrationException('Provider timeout');
                }

                public function fetchAttendance(string $externalMeetingId, \\DateTimeImmutable $from, \\DateTimeImmutable $to): iterable
                {
                    throw new \\App\\Services\\Integrations\\IntegrationException('Provider timeout');
                }
            }
        );

        $this->assertNull(app(LiveClassService::class)->ensureRoom($classroom));
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'skyroom_status' => 'failed',
            'skyroom_note' => 'Provider timeout',
        ]);
    }

}
