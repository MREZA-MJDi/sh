<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Message;
use App\Models\Payment;
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

        $cancelledSession = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه لغوشده',
            'session_type' => 'onsite',
            'starts_at' => now()->subHour(),
            'status' => 'cancelled',
            'created_by' => $admin->id,
        ]);
        Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'class_session_id' => $cancelledSession->id,
            'student_id' => User::factory()->create()->id,
            'session_date' => today()->toDateString(),
            'status' => 'present',
        ]);

        $futureSession = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه آینده',
            'session_type' => 'onsite',
            'starts_at' => now()->addHour(),
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);
        Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'class_session_id' => $futureSession->id,
            'student_id' => User::factory()->create()->id,
            'session_date' => today()->toDateString(),
            'status' => 'present',
        ]);
        Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'student_id' => User::factory()->create()->id,
            'session_date' => today()->toDateString(),
            'status' => 'unknown',
        ]);

        $this->assertDatabaseCount('attendances', 7);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasPermissionTo('attendance.view.all'));
        $this->assertSame(7, Attendance::query()
            ->whereDate('session_date', '>=', today()->copy()->subDays(6)->toDateString())
            ->whereDate('session_date', '<=', today()->toDateString())
            ->count());

        $dashboard = app(DashboardService::class)->build($admin);

        $this->assertSame(50, $dashboard['stats']['attendance_rate']);
        $this->assertSame(4, $dashboard['attendanceTotal']);
        $this->assertCount(7, $dashboard['weeklyAttendance']);
        $today = $dashboard['weeklyAttendance']->firstWhere('label', today()->translatedFormat('l'));
        $this->assertSame(1, $today['present']);
        $this->assertSame(1, $today['late']);
        $this->assertSame(1, $today['absent']);
        $this->assertSame(1, $today['excused']);
        $jalali = \App\Support\JalaliDate::fromGregorian(today());
        $jalaliLabel = sprintf('%02d/%02d', $jalali['month'], $jalali['day']);
        $jalaliLabel = strtr($jalaliLabel, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
        $this->assertSame($jalaliLabel, $today['date_label']);
        $this->assertSame(today()->toDateString(), $today['date_iso']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('ترکیب حضور و غیاب در هفته')
            ->assertSee('is-present')
            ->assertSee('is-late')
            ->assertSee('is-absent')
            ->assertSee('is-excused');
    }


    public function test_admin_dashboard_sales_kpis_only_include_confirmed_paid_course_payments(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $student = User::factory()->create();

        $course = Course::query()->create([
            'title' => 'دوره فروش واقعی',
            'slug' => 'verified-course-sales-dashboard',
            'teacher_id' => $admin->id,
            'type' => 'paid',
            'price' => 2000,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        foreach ([1000, 2000] as $index => $amount) {
            Payment::query()->create([
                'reference' => 'DASH-PAID-'.$index,
                'student_id' => $student->id,
                'course_id' => $course->id,
                'amount' => $amount,
                'method' => 'bank_transfer',
                'provider' => 'manual',
                'status' => 'paid',
                'paid_at' => now()->subDays($index),
            ]);
        }

        Payment::query()->create([
            'reference' => 'DASH-PENDING-1',
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 9000,
            'method' => 'bank_transfer',
            'provider' => 'manual',
            'status' => 'pending_review',
        ]);

        $dashboard = app(DashboardService::class)->build($admin);

        $this->assertSame(2, $dashboard['paidCoursePurchases']);
        $this->assertSame(1500, $dashboard['averageCoursePurchase']);
        $this->assertSame(3000, $dashboard['revenueLast30Days']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('میانگین خرید تأییدشده')
            ->assertSee('درآمد تأییدشده ۳۰ روز اخیر')
            ->assertSee('فروش واقعی دوره‌ها')
            ->assertDontSee('حضور هفتگی');
    }

    public function test_dashboard_shows_an_explicit_empty_state_when_no_attendance_is_recorded(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('در بازه هفت روز اخیر هنوز رکورد حضور و غیاب ثبت نشده است')
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

    public function test_closed_conversations_are_excluded_from_open_message_count(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        Message::query()->create([
            'sender_id' => $admin->id, 'subject' => 'Open', 'body' => 'Open message', 'status' => 'open',
        ]);
        $closed = Message::query()->create([
            'sender_id' => $admin->id, 'subject' => 'Closed', 'body' => 'Closed message', 'status' => 'open',
        ]);
        $closed->forceFill(['closed_at' => now()])->save();

        $this->assertSame(1, app(DashboardService::class)->build($admin)['openMessageCount']);
    }

    public function test_dashboard_exposes_recent_enrollments_pending_payment_reviews_and_upcoming_sessions(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $course = Course::query()->create([
            'title' => 'دوره ثبت‌نام جدید',
            'slug' => 'dashboard-recent-enrollment',
            'teacher_id' => $admin->id,
            'type' => 'paid',
            'price' => 150000,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        CourseEnrollment::query()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        Payment::query()->create([
            'reference' => 'DASH-REVIEW-NEW',
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 150000,
            'method' => 'bank_transfer',
            'provider' => 'manual',
            'status' => 'pending_review',
        ]);

        $classroom = Classroom::query()->create([
            'teacher_id' => $admin->id,
            'name' => 'کلاس جلسه پیش‌رو',
            'status' => 'active',
        ]);
        ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه برنامه‌ریزی‌شده',
            'session_type' => 'onsite',
            'starts_at' => now()->addDay(),
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);

        $dashboard = app(DashboardService::class)->build($admin);

        $this->assertSame(1, $dashboard['recentEnrollmentCount']);
        $this->assertCount(1, $dashboard['recentEnrollments']);
        $this->assertSame(1, $dashboard['pendingPaymentReviewCount']);
        $this->assertCount(1, $dashboard['upcomingSessions']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('الان چه کارهایی باید انجام بدهم؟')
            ->assertSee('رسیدهای پرداخت نیازمند بررسی')
            ->assertSee('ثبت‌نام‌های جدید در ۷ روز اخیر')
            ->assertSee('جلسات پیش‌رو')
            ->assertSee('جلسه برنامه‌ریزی‌شده');
    }

}
