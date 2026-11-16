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
}
