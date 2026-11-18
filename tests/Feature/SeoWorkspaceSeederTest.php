<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SeoArticle;
use App\Models\SeoSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SeoWorkspaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoWorkspaceSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        config([
            'seo.operator.name' => 'کارشناس محتوای سایت',
            'seo.operator.email' => 'seo.operator@example.test',
            'seo.operator.password' => 'Secure-Seo-Password-2026!',
        ]);
    }

    public function test_seo_workspace_seeder_creates_a_dedicated_operator_settings_and_starter_blog_content(): void
    {
        $this->seed(SeoWorkspaceSeeder::class);

        $seo = User::query()->where('email', 'seo.operator@example.test')->firstOrFail();
        $seoRole = Role::query()->where('name', 'seo')->firstOrFail();

        $this->assertTrue($seo->hasRole('seo'));
        $this->assertTrue($seo->hasPermissionTo('seo.manage'));
        $this->assertFalse($seo->hasPermissionTo('dashboard.view'));
        $this->assertFalse($seo->hasPermissionTo('users.manage'));
        $this->assertSame([$seoRole->id], $seo->roles()->pluck('roles.id')->all());

        $this->assertDatabaseHas('seo_settings', ['id' => 1]);
        $this->assertDatabaseHas('seo_articles', [
            'slug' => 'barname-rizi-darsi-movafagh',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('seo_articles', [
            'slug' => 'moroor-faale-va-yadgiri-paayedar',
            'status' => 'published',
        ]);

        $this->actingAs($seo)
            ->get(route('admin.seo.index'))
            ->assertOk()
            ->assertSee('مرکز مدیریت سئو');

        $this->actingAs($seo)
            ->get(route('admin.students.index'))
            ->assertForbidden();

        auth()->logout();
        $this->post(route('login.store'), [
            'email' => 'seo.operator@example.test',
            'password' => 'Secure-Seo-Password-2026!',
        ])->assertRedirect(route('admin.seo.index'));
    }

    public function test_seo_workspace_seeder_is_repeatable_and_does_not_overwrite_existing_editorial_content(): void
    {
        $this->seed(SeoWorkspaceSeeder::class);

        $article = SeoArticle::query()
            ->where('slug', 'barname-rizi-darsi-movafagh')
            ->firstOrFail();
        $article->update(['title' => 'عنوانی که کارشناس سئو ویرایش کرده است']);

        SeoSetting::query()->whereKey(1)->update([
            'site_name' => 'برند ویرایش‌شده',
        ]);

        $this->seed(SeoWorkspaceSeeder::class);

        $this->assertSame('عنوانی که کارشناس سئو ویرایش کرده است', $article->fresh()->title);
        $this->assertSame('برند ویرایش‌شده', SeoSetting::query()->findOrFail(1)->site_name);
        $this->assertDatabaseCount('seo_articles', 2);
    }
}
