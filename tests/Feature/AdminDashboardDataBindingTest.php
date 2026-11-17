<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Consultation;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\DashboardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardDataBindingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_dashboard_action_counters_use_total_records_not_the_limited_preview_collections(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        foreach (range(1, 8) as $index) {
            Message::query()->create([
                'sender_id' => $admin->id,
                'subject' => 'گفت‌وگوی '.$index,
                'body' => 'متن پیام '.$index,
                'status' => 'open',
            ]);
        }

        foreach (range(1, 7) as $index) {
            Consultation::query()->create([
                'requester_id' => $admin->id,
                'type' => 'phone',
                'topic' => 'درخواست مشاوره '.$index,
                'status' => 'pending',
            ]);
        }

        foreach (range(1, 6) as $index) {
            Announcement::query()->create([
                'created_by' => $admin->id,
                'title' => 'اطلاعیه '.$index,
                'body' => 'متن اطلاعیه '.$index,
                'audience' => 'all',
                'status' => 'published',
                'published_at' => now(),
            ]);
        }

        $dashboard = app(DashboardService::class)->build($admin);

        $this->assertSame(8, $dashboard['openMessageCount']);
        $this->assertSame(7, $dashboard['pendingConsultationCount']);
        $this->assertSame(6, $dashboard['publishedAnnouncementCount']);
        $this->assertCount(5, $dashboard['recentMessages']);
        $this->assertCount(5, $dashboard['pendingConsultations']);
        $this->assertCount(4, $dashboard['recentAnnouncements']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('گفت‌وگوهای باز')
            ->assertSee('درخواست‌های مشاوره جدید')
            ->assertSee('اطلاعیه‌های منتشرشده')
            ->assertSee('>8</strong>', false)
            ->assertSee('>7</strong>', false)
            ->assertSee('>6</strong>', false);
    }
}
