<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
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

    public function test_parent_navigation_is_responsive_and_hides_unpermitted_destinations(): void
    {
        $guardian = $this->userWithRole('parent', ['permissions_customized' => true]);
        $guardian->permissions()->sync(
            Permission::query()->where('name', 'dashboard.view')->pluck('id')
        );

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard'))
            ->assertOk()
            ->assertSee('id="parentNavToggle"', false)
            ->assertSee('id="parentSidebar"', false)
            ->assertSee('id="parentNavBackdrop"', false)
            ->assertSee('id="globalSearch"', false)
            ->assertSee('id="parentSearchClear"', false)
            ->assertSee('id="themeToggle"', false)
            ->assertSee('parent-notification-menu', false)
            ->assertSee('parent-profile-menu', false)
            ->assertSee('aria-label="منوی اصلی اولیا"', false)
            ->assertDontSee(route('guardian.attendance'))
            ->assertDontSee(route('guardian.exams'))
            ->assertDontSee(route('guardian.homework'))
            ->assertDontSee(route('guardian.resources.index'))
            ->assertDontSee(route('guardian.messages.index'))
            ->assertDontSee(route('guardian.consultations.index'));
    }

    public function test_parent_list_pages_render_empty_paginators_when_no_child_is_linked(): void
    {
        $guardian = $this->userWithRole('parent');

        $this->actingAs($guardian)->get(route('guardian.homework'))->assertOk();
        $this->actingAs($guardian)->get(route('guardian.exams'))->assertOk();
        $this->actingAs($guardian)->get(route('guardian.resources.index'))->assertOk();
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

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
