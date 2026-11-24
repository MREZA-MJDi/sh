<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
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

        $this->actingAs($guardian)->get(route('guardian.attendance'))->assertForbidden();
        $this->actingAs($guardian)->get(route('guardian.exams'))->assertForbidden();
        $this->actingAs($guardian)->get(route('guardian.homework'))->assertForbidden();
        $this->actingAs($guardian)->get(route('guardian.messages.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('guardian.consultations.index'))->assertForbidden();
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


    public function test_jalali_date_formatter_returns_persian_calendar_date_and_time(): void
    {
        $date = CarbonImmutable::parse('2026-11-22 13:30:00', 'Asia/Tehran');

        $this->assertSame('1405/09/01 13:30', JalaliDate::format($date, true));
        $this->assertSame('1405/09/01', JalaliDate::format($date));
        $this->assertSame('۱۴۰۵/۰۹/۰۱ ۱۳:۳۰', \App\Support\PersianDate::format($date));
        $lastDay = CarbonImmutable::parse('2026-11-21 23:59:00', 'Asia/Tehran');
        $this->assertSame('1405/08/30', JalaliDate::format($lastDay));
        $this->assertSame('۱۴۰۵/۰۸/۳۰ ۲۳:۵۹', \App\Support\PersianDate::format($lastDay));
        $this->assertSame('2026-11-22', JalaliDate::toGregorian(1405, 9, 1)->toDateString());
    }

    public function test_switching_children_clears_stale_message_and_pagination_query_parameters(): void
    {
        $guardian = $this->userWithRole('parent');
        $firstChild = $this->userWithRole('student');
        $secondChild = $this->userWithRole('student');
        $guardian->children()->attach([$firstChild->id, $secondChild->id]);

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard', [
                'child' => $firstChild->id,
                'message' => 999,
                'page' => 4,
                'homework' => 3,
            ]))
            ->assertOk()
            ->assertSee('child='.$secondChild->id)
            ->assertDontSee('message=999')
            ->assertDontSee('homework=3');
    }

    public function test_parent_dashboard_uses_null_for_months_without_homework_data(): void
    {
        $guardian = $this->userWithRole('parent');
        $child = $this->userWithRole('student');
        $guardian->children()->attach($child);

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard'))
            ->assertOk()
            ->assertSee('data-chart-config=', false)
            ->assertSee('null,null,null,null,null,null', false);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
