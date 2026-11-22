<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Notifications\AdminPanelBroadcastNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InternalNotificationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_can_send_an_internal_notice_only_to_active_users_in_the_selected_audience(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

        $studentRole = Role::query()->where('name', 'student')->firstOrFail();
        $firstStudent = User::factory()->create(['is_active' => true]);
        $firstStudent->roles()->attach($studentRole);
        $secondStudent = User::factory()->create(['is_active' => true]);
        $secondStudent->roles()->attach($studentRole);
        $inactiveStudent = User::factory()->create(['is_active' => false]);
        $inactiveStudent->roles()->attach($studentRole);
        $parent = User::factory()->create(['is_active' => true]);
        $parent->roles()->attach(Role::query()->where('name', 'parent')->firstOrFail());

        $this->actingAs($admin)
            ->post(route('admin.internal-notifications.store'), [
                'title' => 'تغییر زمان کلاس',
                'message' => 'جلسه فردا با نیم ساعت تأخیر برگزار می‌شود.',
                'audience' => 'students',
                'url' => '/student/homework',
                'action_label' => 'مشاهده برنامه',
            ])
            ->assertRedirect(route('admin.internal-notifications.index'))
            ->assertSessionHasNoErrors();

        $notifications = DB::table('notifications')
            ->where('type', AdminPanelBroadcastNotification::class)
            ->get();

        $this->assertCount(2, $notifications);
        $payloads = $notifications->map(fn (object $row) => json_decode($row->data, true));
        $this->assertTrue($payloads->every(fn (array $data) => $data['category'] === 'admin_broadcast'
            && $data['audience'] === 'students'
            && $data['title'] === 'تغییر زمان کلاس'
            && $data['url'] === '/student/homework'));
        $this->assertDatabaseCount('announcements', 0);
        $this->assertDatabaseHas('admin_notification_broadcasts', ['audience' => 'students', 'recipient_count' => 2, 'title' => 'تغییر زمان کلاس']);
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $parent->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $inactiveStudent->id)->count());
    }

    public function test_teacher_cannot_send_admin_internal_broadcasts(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->firstOrFail());

        $this->actingAs($teacher)
            ->post(route('admin.internal-notifications.store'), [
                'title' => 'پیام',
                'message' => 'متن پیام',
                'audience' => 'all',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_internal_notification_history_is_separate_from_public_announcements(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
        $student = User::factory()->create(['is_active' => true]);
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

        $this->actingAs($admin)->post(route('admin.internal-notifications.store'), [
            'title' => 'یادآوری آزمون',
            'message' => 'فردا آزمون برگزار می‌شود.',
            'audience' => 'students',
        ])->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.internal-notifications.index'))
            ->assertOk()
            ->assertSee('اعلان‌های درون‌پنلی')
            ->assertSee('یادآوری آزمون')
            ->assertSee('1');
    }
}
