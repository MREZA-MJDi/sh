<?php

namespace App\Services\SpotPlayer;

use App\Models\Course;
use App\Models\IntegrationRun;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerSetting;
use App\Models\User;
use App\Services\Integrations\IntegrationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class SpotPlayerService
{
    public function isConfigured(): bool
    {
        $settings = SpotPlayerSetting::query()->first();

        return (bool) ($settings && filled($settings->api_key));
    }

    public function courses(bool $refresh = false): Collection
    {
        $settings = $this->settings();
        $cacheKey = 'integrations.spotplayer.courses.'.sha1($settings->api_key);

        $loader = fn () => collect($this->client($settings)->courses())
            ->pipe(fn (Collection $items) => $this->normalizeCourseList($items->all()));

        return $refresh
            ? tap($loader(), fn ($items) => Cache::put($cacheKey, $items, now()->addMinutes(5)))
            : Cache::remember($cacheKey, now()->addMinutes(5), $loader);
    }

    public function assertCourseExists(string $externalCourseId): void
    {
        $found = $this->courses()->contains(
            fn (array $course) => (string) ($course['_id'] ?? $course['id'] ?? '') === $externalCourseId
        );

        if (! $found) {
            throw new IntegrationException('دوره انتخاب‌شده در فهرست دوره‌های حساب SpotPlayer پیدا نشد. ابتدا شناسه را از پنل SpotPlayer همگام‌سازی کنید.');
        }
    }

    public function issueLicense(User $student, Course $course): SpotPlayerLicense
    {
        if (! $student->hasRole('student')) {
            throw new IntegrationException('صدور لایسنس فقط برای حساب دارای نقش دانش‌آموز مجاز است.');
        }

        if ($course->delivery_type !== 'spotplayer' || blank($course->spotplayer_course_id)) {
            throw new IntegrationException('این دوره به یک دوره معتبر SpotPlayer متصل نشده است.');
        }

        $settings = $this->settings();

        if ($settings->watermark_enabled && blank($student->phone)) {
            throw new IntegrationException('برای صدور لایسنس با واترمارک، شماره موبایل دانش‌آموز الزامی است.');
        }

        $this->assertCourseExists($course->spotplayer_course_id);

        $license = SpotPlayerLicense::firstOrNew([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        if ($license->exists && $license->status === 'active' && filled($license->external_license_id)) {
            return $license;
        }

        $license->fill([
            'max_devices' => $settings->max_devices,
            'status' => 'pending',
            'error_message' => null,
        ])->save();

        $run = IntegrationRun::create([
            'provider' => 'spotplayer',
            'action' => 'issue_license',
            'entity_type' => Course::class,
            'entity_id' => $course->id,
            'status' => 'running',
            'request_meta' => [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'spotplayer_course_id' => $course->spotplayer_course_id,
                'mode' => $settings->mode,
            ],
            'started_at' => now(),
        ]);

        try {
            $watermark = $settings->watermark_enabled
                ? ['texts' => [['text' => $student->phone]]]
                : ['texts' => []];

            $limit = max(1, min(99, (int) $settings->max_devices));
            $device = array_fill_keys(['p0', 'p1', 'p2', 'p3', 'p4', 'p5', 'p6'], $limit);

            $payload = json_encode([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ], JSON_THROW_ON_ERROR);

            $response = $this->client($settings)->createLicense(
                $student->name,
                [$course->spotplayer_course_id],
                $watermark,
                $device,
                $payload,
            );

            $externalId = $response['_id'] ?? null;
            $licenseKey = $response['key'] ?? null;
            $downloadUrl = $response['url'] ?? null;

            if (! is_string($externalId) || $externalId === '' ||
                ! is_string($licenseKey) || $licenseKey === '' ||
                ! is_string($downloadUrl) || ! str_starts_with($downloadUrl, 'https://')) {
                throw new IntegrationException('پاسخ صدور لایسنس ناقص است؛ رکورد محلی فعال نشد.');
            }

            $license->update([
                'external_license_id' => $externalId,
                'license_key' => $licenseKey,
                'download_url' => $downloadUrl,
                'status' => 'active',
                'issued_at' => now(),
                'error_message' => null,
            ]);

            $run->update([
                'status' => 'succeeded',
                'response_meta' => ['external_license_id' => $externalId],
                'completed_at' => now(),
            ]);

            return $license->refresh();
        } catch (Throwable $exception) {
            $license->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000),
            ]);

            $run->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000),
                'completed_at' => now(),
            ]);

            if ($exception instanceof IntegrationException) {
                throw $exception;
            }

            throw new IntegrationException('صدور لایسنس SpotPlayer انجام نشد.', previous: $exception);
        }
    }

    private function settings(): SpotPlayerSetting
    {
        $settings = SpotPlayerSetting::query()->first();

        if (! $settings || blank($settings->api_key)) {
            throw new IntegrationException('کلید API اسپات پلیر را ابتدا در تنظیمات ثبت کنید.');
        }

        return $settings;
    }

    private function client(SpotPlayerSetting $settings): SpotPlayerClient
    {
        return new SpotPlayerClient($settings->api_key, $settings->mode ?: 'test');
    }

    private function normalizeCourseList(array $payload): Collection
    {
        if (array_is_list($payload)) {
            return collect($payload)->filter(fn ($item) => is_array($item) && filled($item['_id'] ?? $item['id'] ?? null))->values();
        }

        foreach (['courses', 'data', 'items'] as $key) {
            $items = data_get($payload, $key);

            if (is_array($items)) {
                return collect($items)
                    ->filter(fn ($item) => is_array($item) && filled($item['_id'] ?? $item['id'] ?? null))
                    ->values();
            }
        }

        if (filled($payload['_id'] ?? $payload['id'] ?? null)) {
            return collect([$payload]);
        }

        throw new IntegrationException('ساختار فهرست دوره‌های برگشتی از SpotPlayer قابل تشخیص نیست.');
    }
}
