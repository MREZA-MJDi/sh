<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianDashboardChildSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_guardian_can_switch_between_linked_children_without_exposing_unlinked_children(): void
    {
        $guardian = $this->userWithRole('parent');
        $firstChild = $this->userWithRole('student', ['name' => 'آرمان']);
        $secondChild = $this->userWithRole('student', ['name' => 'زهرا']);
        $unlinkedChild = $this->userWithRole('student', ['name' => 'دانش‌آموز محرمانه']);

        $guardian->children()->attach([$firstChild->id, $secondChild->id]);

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard', ['child' => $secondChild->id]))
            ->assertOk()
            ->assertSee('وضعیت آموزشی زهرا')
            ->assertSee('value="'.$secondChild->id.'" selected', false)
            ->assertDontSee('دانش‌آموز محرمانه');

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard', ['child' => $unlinkedChild->id]))
            ->assertOk()
            ->assertSee('وضعیت آموزشی آرمان')
            ->assertDontSee('وضعیت آموزشی دانش‌آموز محرمانه');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
