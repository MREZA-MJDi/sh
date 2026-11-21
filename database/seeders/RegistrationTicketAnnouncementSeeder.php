<?php

namespace Database\\Seeders;

use App\\Models\\Announcement;
use Illuminate\\Database\\Seeder;

class RegistrationTicketAnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        if (Announcement::query()->where('display_as_ticket', true)->exists()) {
            return;
        }

        Announcement::query()->create([
            'title' => 'مسیر آموزشی خودت را از همین‌جا آغاز کن',
            'body' => 'دوره‌های آموزشی آکادمی شیخان را بررسی کن و مسیر متناسب با هدف تحصیلی خودت را انتخاب کن. برای دریافت راهنمایی بیشتر می‌توانی با آکادمی در ارتباط باشی.',
            'audience' => 'all',
            'status' => 'published',
            'published_at' => now(),
            'starts_at' => null,
            'ends_at' => null,
            'priority' => 'normal',
            'action_url' => '/courses',
            'action_label' => 'مشاهده دوره‌ها',
            'display_as_ticket' => true,
        ]);
    }
}
