<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SeoSetting;
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

    public function test_guest_panel_menu_offers_authentication_instead_of_role_dashboards(): void
    {
        $menu = $this->renderPanelMenu();

        $this->assertStringContainsString('ورود به حساب', $menu);
        $this->assertStringContainsString(route('login'), $menu);
        $this->assertStringContainsString(route('register'), $menu);
        $this->assertStringNotContainsString('پنل دانش‌آموز', $menu);
        $this->assertStringNotContainsString('پنل مدرس', $menu);
        $this->assertStringNotContainsString('پنل اولیا', $menu);
        $this->assertStringNotContainsString('پنل مدیریت', $menu);
    }

    public function test_teacher_sees_only_the_teacher_workspace_in_the_public_panel_menu(): void
    {
        $teacher = $this->userWithRole('teacher');
        $this->actingAs($teacher);

        $menu = $this->renderPanelMenu();

        $this->assertStringContainsString('پنل مدرس', $menu);
        $this->assertStringContainsString('href="'.route('teacher.dashboard').'"', $menu);
        $this->assertStringNotContainsString('href="'.route('student.dashboard').'"', $menu);
        $this->assertStringNotContainsString('href="'.route('guardian.dashboard').'"', $menu);
        $this->assertStringNotContainsString('href="'.route('admin.dashboard').'"', $menu);
    }

    public function test_seo_user_is_sent_to_the_seo_workspace_not_the_admin_dashboard(): void
    {
        $seo = $this->userWithRole('seo');
        $this->actingAs($seo);

        $menu = $this->renderPanelMenu();

        $this->assertStringContainsString('پنل سئو و محتوا', $menu);
        $this->assertStringContainsString('href="'.route('admin.seo.index').'"', $menu);
        $this->assertStringNotContainsString('href="'.route('admin.dashboard').'"', $menu);
        $this->assertStringNotContainsString('پنل دانش‌آموز', $menu);
        $this->assertStringNotContainsString('پنل مدرس', $menu);
        $this->assertStringNotContainsString('پنل اولیا', $menu);
    }

    private function renderPanelMenu(): string
    {
        $html = view('public.partials.header', [
            'settings' => SeoSetting::current(),
        ])->render();

        $marker = '<!-- منوی بازشونده پنل من -->';
        $position = strpos($html, $marker);

        $this->assertNotFalse($position, 'The public panel menu was not rendered.');

        return substr($html, $position);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }
}
