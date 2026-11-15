<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/** روی مدل Course استفاده شود. */
trait HasLearningAccess
{
    public static function bootHasLearningAccess(): void
    {
        static::saved(fn () => Cache::increment('course_pages_v'));
        static::deleted(fn () => Cache::increment('course_pages_v'));
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function isFree(): bool
    {
        return $this->access_type === 'free';
    }

    public function needsLicense(): bool
    {
        return filled($this->external_course_id);
    }

    public function getTeaserUrlAttribute(): ?string
    {
        return $this->teaser_path ? Storage::disk('public')->url($this->teaser_path) : null;
    }

    public function getTeaserPosterUrlAttribute(): ?string
    {
        return $this->teaser_poster_path ? Storage::disk('public')->url($this->teaser_poster_path) : null;
    }

    /** مشکلاتی که جلوی انتشار را می‌گیرند. */
    public function publishProblems(): array
    {
        $p = [];

        if (! $this->isFree() && (int) $this->price <= 0) {
            $p[] = 'قیمت دوره پولی باید بزرگ‌تر از صفر باشد.';
        }

        $publishedLessons = fn ($q) => $q->where('status', 'published');

        if (! $this->sections()->whereHas('lessons', $publishedLessons)->exists()) {
            $p[] = 'حداقل یک درس منتشرشده لازم است.';
        }

        $hasVideo = $this->sections()->whereHas('lessons', fn ($q) => $q->where('status', 'published')->whereNotNull('external_video_id'))->exists();
        if ($hasVideo && ! $this->needsLicense()) {
            $p[] = 'برای دوره دارای ویدیو، External Course ID سرویس پخش لازم است.';
        }

        return $p;
    }
}
