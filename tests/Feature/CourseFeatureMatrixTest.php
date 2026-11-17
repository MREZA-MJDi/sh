<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CourseFeatureMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->withoutVite();
        config(['services.spotplayer.embed_hosts' => ['player.example.test']]);
    }

    public function test_hybrid_course_can_enable_recorded_video_and_live_class_together(): void
    {
        $course = Course::query()->create([
            'title' => 'دوره ترکیبی',
            'slug' => 'hybrid-course-feature-matrix',
            'type' => 'paid',
            'price' => 900000,
            'delivery_type' => 'skyroom',
            'learning_features' => [
                'recorded_video' => true,
                'live_class' => true,
                'resources' => true,
                'assignments' => true,
                'exams' => false,
                'projects' => false,
            ],
            'status' => 'published',
        ]);

        $this->assertTrue($course->featureEnabled('recorded_video'));
        $this->assertTrue($course->featureEnabled('live_class'));
        $this->assertFalse($course->featureEnabled('exams'));
        $this->assertSame('skyroom', $course->delivery_type);
    }

    public function test_legacy_course_keeps_its_delivery_defaults_until_feature_matrix_is_configured(): void
    {
        $videoCourse = new Course(['delivery_type' => 'spotplayer']);
        $liveCourse = new Course(['delivery_type' => 'skyroom']);

        $this->assertTrue($videoCourse->featureEnabled('recorded_video'));
        $this->assertFalse($videoCourse->featureEnabled('live_class'));
        $this->assertTrue($liveCourse->featureEnabled('live_class'));
        $this->assertFalse($liveCourse->featureEnabled('recorded_video'));
        $this->assertTrue($liveCourse->featureEnabled('exams'));
    }

    public function test_hybrid_course_with_video_entitlement_can_show_spotplayer_player_even_when_legacy_delivery_type_is_skyroom(): void
    {
        $student = User::factory()->create(['is_active' => true]);
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

        $course = Course::query()->create([
            'title' => 'دوره ترکیبی ویدیویی',
            'slug' => 'hybrid-course-video-entitlement',
            'type' => 'paid',
            'price' => 900000,
            'delivery_type' => 'skyroom',
            'learning_features' => [
                'recorded_video' => true,
                'live_class' => true,
                'resources' => false,
                'assignments' => false,
                'exams' => false,
                'projects' => false,
            ],
            'status' => 'published',
        ]);

        CourseEnrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_at' => now(),
        ]);

        SpotPlayerLicense::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'external_license_id' => 'hybrid-license',
            'license_key' => 'hybrid-license-key',
            'status' => 'active',
            'issued_at' => now(),
        ]);

        SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'title' => 'ویدیوی ترکیبی',
            'external_id' => 'hybrid-video-1',
            'embed_url' => 'https://player.example.test/embed/hybrid-video-1',
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('تماشای ویدیوهای دوره')
            ->assertSee('ویدیوی ترکیبی');
    }

    public function test_explicitly_disabled_recorded_video_blocks_direct_player_route_even_if_old_spotplayer_license_exists(): void
    {
        $student = User::factory()->create(['is_active' => true]);
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

        $course = Course::query()->create([
            'title' => 'دوره بدون ویدیو',
            'slug' => 'course-video-disabled',
            'type' => 'paid',
            'price' => 900000,
            'delivery_type' => 'spotplayer',
            'learning_features' => [
                'recorded_video' => false,
                'live_class' => true,
                'resources' => true,
                'assignments' => true,
                'exams' => true,
                'projects' => false,
            ],
            'status' => 'published',
        ]);

        CourseEnrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_at' => now(),
        ]);

        SpotPlayerLicense::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'external_license_id' => 'old-license',
            'license_key' => 'old-license-key',
            'status' => 'active',
            'issued_at' => now(),
        ]);

        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'title' => 'ویدیوی غیرفعال',
            'external_id' => 'disabled-video',
            'embed_url' => 'https://player.example.test/embed/disabled-video',
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->get(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]))
            ->assertNotFound();
    }
}
