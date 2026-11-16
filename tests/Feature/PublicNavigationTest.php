<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_guest_panel_menu_offers_authentication_instead_of_unavailable_role_dashboards(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('ورود به حساب')
            ->assertSee(route('login'))
            ->assertSee(route('register'))
            ->assertDontSee('پنل دانش‌آموز')
            ->assertDontSee('پنل مدرس')
            ->assertDontSee('پنل اولیا')
            ->assertDontSee('پنل مدیریت');
    }

    public function test_teacher_sees_only_the_teacher_workspace_in_the_public_panel_menu(): void
    {
        $teacher = $this->userWithRole('teacher');

        $this->actingAs($teacher)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('پنل مدرس')
            ->assertSee('href="'.route('teacher.dashboard').'"', false)
            ->assertDontSee('href="'.route('student.dashboard').'"', false)
            ->assertDontSee('href="'.route('guardian.dashboard').'"', false)
            ->assertDontSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_seo_user_is_sent_to_the_seo_workspace_not_the_admin_dashboard(): void
    {
        $seo = $this->userWithRole('seo');

        $this->actingAs($seo)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('پنل سئو و محتوا')
            ->assertSee('href="'.route('admin.seo.index').'"', false)
            ->assertDontSee('href="'.route('admin.dashboard').'"', false)
            ->assertDontSee('پنل دانش‌آموز')
            ->assertDontSee('پنل مدرس')
            ->assertDontSee('پنل اولیا');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }
}
