<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AdminTeacherWorkspacePolishTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_can_read_only_their_own_notifications_from_the_topbar(): void
    {
        $teacher = $this->userWithRole('teacher');
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $teacher->id,
            'data' => json_encode([
                'title' => 'پیام جدید برای مدرس',
                'message' => 'یک پیام تازه دریافت کرده‌اید.',
                'url' => route('teacher.messages.index'),
            ], JSON_UNESCAPED_UNICODE),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('پیام جدید برای مدرس')
            ->assertSee(route('notifications.read', $notificationId), false)
            ->assertSee(route('teacher.consultations.index'), false);

        $this->actingAs($teacher)
            ->patch(route('notifications.read', $notificationId))
            ->assertRedirect(route('teacher.messages.index'));

        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));

        $otherTeacher = $this->userWithRole('teacher');
        $this->actingAs($otherTeacher)
            ->patch(route('notifications.read', $notificationId))
            ->assertNotFound();
    }

    public function test_admin_topbar_uses_real_notifications_instead_of_static_sample_alerts(): void
    {
        $admin = $this->userWithRole('admin');
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'data' => json_encode([
                'title' => 'اعلان واقعی مدیریت',
                'message' => 'این اعلان از جدول notifications خوانده شده است.',
                'url' => route('admin.dashboard'),
            ], JSON_UNESCAPED_UNICODE),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('اعلان واقعی مدیریت')
            ->assertDontSee('۷ پرداخت منتظر تأیید است');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }
}
