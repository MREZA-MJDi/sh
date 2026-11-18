<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianPanelExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_guardian_dashboard_exposes_logout_and_chart_regions(): void
    {
        $guardian = $this->userWithRole('parent');
        $child = $this->userWithRole('student');
        $guardian->children()->attach($child);

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard'))
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('خروج از حساب')
            ->assertSee('data-chart="line"', false)
            ->assertSee('data-chart="donut"', false)
            ->assertSee('data-chart="bar"', false);
    }

    public function test_guardian_can_log_out_through_the_shared_session_endpoint(): void
    {
        $guardian = $this->userWithRole('parent');

        $this->actingAs($guardian)
            ->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
