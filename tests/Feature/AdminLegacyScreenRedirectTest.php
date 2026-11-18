<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminLegacyScreenRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_legacy_demo_screen_urls_redirect_to_the_live_workflows(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $destinations = [
            '/admin/assignments' => route('admin.homework.index'),
            '/admin/recordings' => route('admin.spotplayer.index'),
            '/admin/meetings' => route('admin.parent-meetings.index'),
            '/admin/parent-messages' => route('admin.messages.index'),
        ];

        foreach ($destinations as $legacyUrl => $destination) {
            $this->actingAs($admin)
                ->get($legacyUrl)
                ->assertRedirect($destination);
        }
    }
}