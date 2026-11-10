<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ConsultationStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_and_guardian_share_the_same_consultation_status_contract(): void
    {
        $admin = $this->userWithRole('admin');
        $guardian = $this->userWithRole('parent');
        $student = $this->userWithRole('student');
        $guardian->children()->attach($student->id);

        $consultation = Consultation::query()->create([
            'requester_id' => $guardian->id,
            'student_id' => $student->id,
            'type' => 'phone',
            'topic' => 'پیگیری وضعیت تحصیلی',
            'desired_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.consultations.update', $consultation), [
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'notes' => 'جلسه برای هفته آینده هماهنگ شد.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($guardian)
            ->get(route('guardian.consultations.index'))
            ->assertOk()
            ->assertSee('هماهنگ‌شده');

        $this->actingAs($admin)
            ->patch(route('admin.consultations.update', $consultation), [
                'status' => 'done',
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'notes' => 'جلسه برگزار شد.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'done',
        ]);

        $this->actingAs($guardian)
            ->get(route('guardian.consultations.show', $consultation))
            ->assertOk()
            ->assertSee('انجام‌شده');
    }

    public function test_legacy_admin_status_values_are_normalized_before_persistence(): void
    {
        $admin = $this->userWithRole('admin');
        $guardian = $this->userWithRole('parent');
        $student = $this->userWithRole('student');
        $consultation = Consultation::query()->create([
            'requester_id' => $guardian->id,
            'student_id' => $student->id,
            'type' => 'ticket',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.consultations.update', $consultation), [
                'status' => 'approved',
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'notes' => 'هماهنگ شد.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'scheduled',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
