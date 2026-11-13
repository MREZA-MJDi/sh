<?php

namespace App\Services\Integrations;

final class IntegrationPreviewService
{
    public function spotPlayer(): array
    {
        return [
            'mode' => 'preview',
            'course_title' => 'نمونه دوره آموزشی',
            'video_title' => 'جلسه اول — معرفی دوره',
            'student_name' => 'دانش‌آموز نمونه',
            'license_status' => 'waiting_for_provider',
            'player_state' => 'ready_after_license',
            'watermark' => 'شماره همراه دانش‌آموز',
            'device_limit' => 2,
            'notice' => 'این داده نمایشی است؛ لایسنس یا پخش واقعی صادر نشده است.',
        ];
    }

    public function quiz24(): array
    {
        return [
            'mode' => 'preview',
            'exam_title' => 'آزمون نمونه فصل اول',
            'student_name' => 'دانش‌آموز نمونه',
            'score' => 18,
            'max_score' => 20,
            'correct' => 18,
            'incorrect' => 2,
            'result_state' => 'sample_import',
            'notice' => 'این نتیجه نمونه است و در کارنامه هیچ دانش‌آموزی ذخیره نشده است.',
        ];
    }

    public function skyroom(): array
    {
        return [
            'mode' => 'preview',
            'class_title' => 'کلاس آنلاین نمونه',
            'session_title' => 'جلسه هفتگی',
            'duration_minutes' => 90,
            'attendees' => 18,
            'late' => 2,
            'absent' => 1,
            'notice' => 'آمار شرکت‌کنندگان نمونه است؛ حضور واقعی فقط پس از اتصال رویدادهای Skyroom ثبت می‌شود.',
        ];
    }
}
