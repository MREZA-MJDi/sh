<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicCourseDeliverySummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_course_page_shows_delivery_and_class_counts_without_exposing_private_room_links(): void
    {
        $teacher = User::factory()->create();
        $course = Course::query()->create([
            'title' => 'دوره آنلاین نمونه',
            'slug' => 'public-live-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'skyroom',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس خصوصی عصر',
            'status' => 'active',
            'meeting_url' => 'https://example.invalid/private-room',
        ]);
        ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه آنلاین خصوصی',
            'session_type' => 'online',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'meeting_provider' => 'skyroom',
            'meeting_url' => 'https://example.invalid/private-session',
            'status' => 'scheduled',
            'created_by' => $teacher->id,
        ]);

        $this->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('آموزش زنده')
            ->assertSee('1 گروه کلاسی فعال')
            ->assertSee('1 جلسه آنلاین پیش‌رو')
            ->assertDontSee('کلاس خصوصی عصر')
            ->assertDontSee('private-room')
            ->assertDontSee('private-session');
    }
}
