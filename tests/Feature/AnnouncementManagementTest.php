<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AnnouncementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_can_update_announcement_content_audience_and_publication_state(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

        $announcement = Announcement::query()->create([
            'title' => 'عنوان اولیه',
            'body' => 'متن اولیه',
            'audience' => 'students',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.announcements.update', $announcement), [
                'title' => 'عنوان اصلاح‌شده',
                'body' => 'متن اصلاح‌شده',
                'audience' => 'parents',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'عنوان اصلاح‌شده',
            'body' => 'متن اصلاح‌شده',
            'audience' => 'parents',
            'status' => 'published',
        ]);
        $this->assertNotNull($announcement->fresh()->published_at);
    }
}
