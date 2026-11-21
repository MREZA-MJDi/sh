<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use App\Services\AnnouncementFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_only_returns_active_all_audience_announcements(): void
    {
        Announcement::query()->create([
            'title' => 'Public active', 'body' => 'Visible', 'audience' => 'all',
            'status' => 'published', 'published_at' => now()->subMinute(),
            'starts_at' => now()->subMinute(), 'ends_at' => now()->addHour(),
            'priority' => 'normal',
        ]);
        Announcement::query()->create([
            'title' => 'Private', 'body' => 'Private', 'audience' => 'students',
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        Announcement::query()->create([
            'title' => 'Scheduled', 'body' => 'Later', 'audience' => 'all',
            'status' => 'published', 'published_at' => now()->subMinute(),
            'starts_at' => now()->addHour(),
        ]);
        Announcement::query()->create([
            'title' => 'Expired', 'body' => 'Old', 'audience' => 'all',
            'status' => 'published', 'published_at' => now()->subDay(),
            'ends_at' => now()->subMinute(),
        ]);

        $feed = app(AnnouncementFeed::class)->forAudience('public');

        $this->assertSame(['Public active'], $feed->pluck('title')->all());
    }

    public function test_role_feed_includes_all_and_matching_audience_only(): void
    {
        foreach (['all', 'students', 'parents', 'teachers'] as $audience) {
            Announcement::query()->create([
                'title' => $audience, 'body' => 'Body', 'audience' => $audience,
                'status' => 'published', 'published_at' => now()->subMinute(),
            ]);
        }

        $feed = app(AnnouncementFeed::class)->forAudience('students');

        $this->assertEqualsCanonicalizing(['all', 'students'], $feed->pluck('title')->all());
    }

    public function test_read_state_is_scoped_to_each_user(): void
    {
        $announcement = Announcement::query()->create([
            'title' => 'Shared', 'body' => 'Body', 'audience' => 'all',
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        $first = User::factory()->create();
        $second = User::factory()->create();

        $announcement->readers()->syncWithoutDetaching([$first->id => ['read_at' => now()]]);

        $firstFeed = app(AnnouncementFeed::class)->forAudience('students', $first);
        $secondFeed = app(AnnouncementFeed::class)->forAudience('students', $second);

        $this->assertTrue((bool) $firstFeed->first()->is_read);
        $this->assertFalse((bool) $secondFeed->first()->is_read);
    }
}
