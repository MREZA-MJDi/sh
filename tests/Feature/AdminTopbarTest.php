<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SeoWorkspaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminTopbarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        config([
            'seo.operator.name' => 'کارشناس محتوای سایت',
            'seo.operator.email' => 'seo.operator@example.test',
            'seo.operator.password' => 'Secure-Seo-Password-2026!',
        ]);
        $this->seed(SeoWorkspaceSeeder::class);
    }

    public function test_admin_topbar_renders_notification_and_profile_panels_without_inline_layout_styles(): void
    {
        $seoOperator = User::query()->where('email', 'seo.operator@example.test')->firstOrFail();

        $response = $this->actingAs($seoOperator)->get(route('admin.seo.index'));

        $response->assertOk()
            ->assertSee('adm-topbar')
            ->assertSee('adm-notification-pop')
            ->assertSee('adm-profile-pop')
            ->assertSee('data-admin-topbar-menu', false)
            ->assertDontSee('style="position:relative"', false)
            ->assertDontSee('style="width:220px"', false);
    }
}
