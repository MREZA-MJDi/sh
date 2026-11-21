<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
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


    public function test_weekly_attendance_kpi_aggregates_the_full_seven_day_window(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();

        $classroom = Classroom::query()->create([
            'teacher_id' => $admin->id,
            'name' => 'کلاس آمار حضور',
            'status' => 'active',
        ]);

        Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'session_date' => today()->toDateString(),
            'status' => 'present',
        ]);
        Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $otherStudent->id,
            'session_date' => today()->toDateString(),
            'status' => 'absent',
        ]);
        Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => User::factory()->create()->id,
            'session_date' => today()->toDateString(),
            'status' => 'late',
        ]);
        Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => User::factory()->create()->id,
            'session_date' => today()->toDateString(),
            'status' => 'excused',
        ]);
        $this->assertDatabaseCount('attendances', 4);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasPermissionTo('attendance.view.all'));
        $this->assertSame(4, Attendance::query()
            ->whereDate('session_date', '>=', today()->copy()->subDays(6)->toDateString())
            ->whereDate('session_date', '<=', today()->toDateString())
            ->count());

        $dashboard = app(DashboardService::class)->build($admin);

        $this->assertSame(50, $dashboard['stats']['attendance_rate']);
        $this->assertSame(4, $dashboard['attendanceTotal']);
        $this->assertCount(7, $dashboard['weeklyAttendance']);
        $today = $dashboard['weeklyAttendance']->firstWhere('label', today()->translatedFormat('D'));
        $this->assertSame(1, $today['present']);
        $this->assertSame(1, $today['late']);
        $this->assertSame(1, $today['absent']);
        $this->assertSame(1, $today['excused']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('ترکیب حضور و غیاب در هفته')
            ->assertSee('is-present')
            ->assertSee('is-late')
            ->assertSee('is-absent')
            ->assertSee('is-excused');
    }

    public function test_dashboard_shows_an_explicit_empty_state_when_no_attendance_is_recorded(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('هنوز داده واقعی برای نمودار نداریم')
            ->assertDontSee('adm-week-segment');
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
        $this->assertArrayNotHasKey('recentMessages', $dashboard);
        $this->assertArrayNotHasKey('pendingConsultations', $dashboard);
        $this->assertArrayNotHasKey('recentAnnouncements', $dashboard);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('گفت‌وگوهای باز')
            ->assertSee('درخواست‌های مشاوره جدید')
            ->assertSee('اطلاعیه‌های منتشرشده')
            ->assertSee('>8</strong>', false)
            ->assertSee('>7</strong>', false)
            ->assertSee('>6</strong>', false)
            ->assertDontSee('گروه‌های آموزشی فعال</th>', false)
            ->assertDontSee('پیام‌های اخیر');
    }
}
