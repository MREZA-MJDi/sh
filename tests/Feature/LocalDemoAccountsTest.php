<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LocalDemoAccountsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalDemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_database_seeder_creates_one_active_account_for_each_panel_when_demo_is_enabled(): void
    {
        config([
            'demo.enabled' => true,
            'demo.password' => 'LocalOnlyDemoPassword!2026',
        ]);

        $this->seed(DatabaseSeeder::class);

        $accounts = [
            'admin@sh.local' => 'admin',
            'teacher@sh.local' => 'teacher',
            'student@sh.local' => 'student',
            'parent@sh.local' => 'parent',
        ];

        foreach ($accounts as $email => $roleName) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue($user->is_active);
            $this->assertTrue($user->hasRole($roleName));
            $this->assertTrue(Hash::check('LocalOnlyDemoPassword!2026', $user->password));
        }

        $parent = User::query()->where('email', 'parent@sh.local')->firstOrFail();
        $student = User::query()->where('email', 'student@sh.local')->firstOrFail();

        $this->assertTrue($parent->children()->whereKey($student->getKey())->exists());
    }

    public function test_seeded_accounts_can_log_in_and_are_redirected_to_their_own_panels(): void
    {
        config([
            'demo.enabled' => true,
            'demo.password' => 'LocalOnlyDemoPassword!2026',
        ]);

        $this->seed(DatabaseSeeder::class);

        $accounts = [
            'admin@sh.local' => 'admin.dashboard',
            'teacher@sh.local' => 'admin.dashboard',
            'student@sh.local' => 'student.dashboard',
            'parent@sh.local' => 'guardian.dashboard',
        ];

        foreach ($accounts as $email => $dashboardRoute) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->post(route('login.store'), [
                'email' => $email,
                'password' => 'LocalOnlyDemoPassword!2026',
            ])->assertRedirect(route($dashboardRoute));

            $this->assertAuthenticatedAs($user);

            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_demo_accounts_are_not_seeded_in_production_even_when_the_flag_is_enabled(): void
    {
        $this->app['env'] = 'production';

        config([
            'demo.enabled' => true,
            'demo.password' => 'LocalOnlyDemoPassword!2026',
        ]);

        app(LocalDemoAccountsSeeder::class)->run();

        foreach (['admin@sh.local', 'teacher@sh.local', 'student@sh.local', 'parent@sh.local'] as $email) {
            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_local_login_page_displays_demo_credentials_and_quick_fill_buttons(): void
    {
        $this->app['env'] = 'local';

        config([
            'demo.enabled' => true,
            'demo.password' => 'LocalOnlyDemoPassword!2026',
        ]);

        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('ورود سریع برای بررسی پنل‌ها')
            ->assertSee('admin@sh.local')
            ->assertSee('teacher@sh.local')
            ->assertSee('student@sh.local')
            ->assertSee('parent@sh.local')
            ->assertSee('LocalOnlyDemoPassword!2026')
            ->assertSee('data-demo-account', false);
    }

    public function test_production_login_page_never_exposes_demo_credentials(): void
    {
        $this->app['env'] = 'production';

        config([
            'demo.enabled' => true,
            'demo.password' => 'LocalOnlyDemoPassword!2026',
        ]);

        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertDontSee('admin@sh.local')
            ->assertDontSee('teacher@sh.local')
            ->assertDontSee('student@sh.local')
            ->assertDontSee('parent@sh.local')
            ->assertDontSee('LocalOnlyDemoPassword!2026');
    }
}
