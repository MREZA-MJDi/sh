<?php

namespace App\Services\Learning;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use RuntimeException;

/**
 * ایمپورت گزارش «نشست‌ها» از پنل اسکای‌روم (خروجی اکسل → ذخیره به‌صورت CSV با UTF-8).
 * فرمت دقیق گزارش دیده نشده؛ اسم ستون‌ها در config/learning.php قابل تنظیم است.
 */
class AttendanceImporter
{
    public function fromCsv(ClassSession $session, string $path): array
    {
        $h = fopen($path, 'r');
        $first = fgetcsv($h);
        if (! $first) {
            throw new RuntimeException('فایل خالی است.');
        }

        $header = array_map(fn ($c) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c)), $first);
        $nameCol = array_search(config('learning.skyroom_report.name_column', 'Nickname'), $header, true);
        $durCol = array_search(config('learning.skyroom_report.duration_column', 'Duration'), $header, true);

        if ($nameCol === false || $durCol === false) {
            throw new RuntimeException('ستون نام یا مدت حضور پیدا نشد؛ config/learning.php را تنظیم کن.');
        }

        $minutes = [];
        while (($row = fgetcsv($h)) !== false) {
            if (! preg_match('/#(\d+)/', $row[$nameCol] ?? '', $m)) {
                continue;
            }
            $uid = (int) $m[1];
            $minutes[$uid] = ($minutes[$uid] ?? 0) + $this->toMinutes((string) ($row[$durCol] ?? '0')); // چند بار ورود و خروج جمع می‌شود
        }
        fclose($h);

        $length = max(1, $session->starts_at->diffInMinutes($session->ends_at));
        $enrolled = Enrollment::where('course_id', $session->course_id)->where('status', 'active')->pluck('user_id');

        foreach ($enrolled as $userId) {
            // رکوردهای «موجه» (excused) دستی بازنویسی نمی‌شوند
            $existing = Attendance::where('class_session_id', $session->id)->where('user_id', $userId)->first();
            if ($existing && $existing->status === 'excused') {
                continue;
            }

            $mins = $minutes[$userId] ?? 0;
            $status = $mins >= $length * 0.6 ? 'present' : ($mins > 0 ? 'late' : 'absent');

            Attendance::updateOrCreate(
                ['class_session_id' => $session->id, 'user_id' => $userId],
                ['status' => $status, 'minutes_present' => $mins, 'source' => 'skyroom_import']
            );
        }

        return ['matched' => count($minutes), 'enrolled' => $enrolled->count()];
    }

    private function toMinutes(string $v): int
    {
        if (str_contains($v, ':')) { // HH:MM:SS
            [$h, $m] = array_pad(explode(':', $v), 3, 0);

            return (int) $h * 60 + (int) $m;
        }

        return (int) $v;
    }
}
