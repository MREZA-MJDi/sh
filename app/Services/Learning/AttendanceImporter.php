<?php

namespace App\Services\Learning;

use App\Models\Attendance;
use App\Models\ClassSession;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceImporter
{
    /**
     * Import a Skyroom CSV report into the canonical classroom/session table.
     * Participant labels can contain a local user ID (#123) or an external
     * student identifier configured on the student profile.
     */
    public function fromCsv(ClassSession $session, string $path): array
    {
        $session->loadMissing('classroom.students');
        $classroom = $session->classroom;

        if (! $classroom) {
            throw new RuntimeException('کلاس این جلسه پیدا نشد.');
        }

        if ($session->status === 'cancelled') {
            throw new RuntimeException('برای جلسه لغوشده نمی‌توان گزارش حضور و غیاب وارد کرد.');
        }

        if (! $session->ends_at || $session->ends_at->lessThanOrEqualTo($session->starts_at)) {
            throw new RuntimeException('برای وارد کردن گزارش، زمان شروع و پایان معتبر جلسه لازم است.');
        }

        if ($session->ends_at->isFuture()) {
            throw new RuntimeException('گزارش حضور فقط پس از پایان جلسه قابل وارد کردن است.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('فایل گزارش حضور قابل خواندن نیست.');
        }

        try {
            $first = fgetcsv($handle);
            if (! $first) {
                throw new RuntimeException('فایل خالی است.');
            }

            $header = array_map(
                fn ($column) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $column)),
                $first
            );
            $nameColumn = array_search(config('learning.skyroom_report.name_column', 'Nickname'), $header, true);
            $durationColumn = array_search(config('learning.skyroom_report.duration_column', 'Duration'), $header, true);

            if ($nameColumn === false || $durationColumn === false) {
                throw new RuntimeException('ستون نام یا مدت حضور پیدا نشد؛ config/learning.php را تنظیم کنید.');
            }

            $referenceMap = [];
            foreach ($classroom->students as $student) {
                $referenceMap[(string) $student->id] = (int) $student->id;
                if (filled($student->external_student_id)) {
                    $referenceMap[$this->normalizeDigits((string) $student->external_student_id)] = (int) $student->id;
                }
            }

            $minutesByStudent = [];
            while (($row = fgetcsv($handle)) !== false) {
                $participant = (string) ($row[$nameColumn] ?? '');
                if (! preg_match('/#\s*([^\s#]+)/u', $participant, $matches)) {
                    continue;
                }

                $reference = $this->normalizeDigits($matches[1]);
                $studentId = $referenceMap[$reference] ?? null;
                if (! $studentId) {
                    continue;
                }

                $minutesByStudent[$studentId] = ($minutesByStudent[$studentId] ?? 0)
                    + $this->toMinutes((string) ($row[$durationColumn] ?? '0'));
            }
        } finally {
            fclose($handle);
        }

        $scheduledMinutes = max(1, intdiv(
            $session->ends_at->getTimestamp() - $session->starts_at->getTimestamp(),
            60
        ));
        $matched = 0;

        DB::transaction(function () use ($classroom, $session, $minutesByStudent, $scheduledMinutes, &$matched): void {
            foreach ($classroom->students as $student) {
                $existing = Attendance::query()
                    ->where('class_session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->first();

                // Keep a teacher's explicit excused decision intact.
                if ($existing?->status === 'excused') {
                    continue;
                }

                $minutes = min($scheduledMinutes, max(0, (int) ($minutesByStudent[$student->id] ?? 0)));
                if (array_key_exists($student->id, $minutesByStudent)) {
                    $matched++;
                }

                $status = $minutes >= (int) ceil($scheduledMinutes * 0.6)
                    ? 'present'
                    : ($minutes > 0 ? 'late' : 'absent');

                Attendance::query()->updateOrCreate(
                    [
                        'class_session_id' => $session->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'classroom_id' => $classroom->id,
                        'session_date' => $session->starts_at->toDateString(),
                        'scheduled_minutes' => $scheduledMinutes,
                        'attended_minutes' => $minutes,
                        'status' => $status,
                    ]
                );
            }
        });

        return ['matched' => $matched, 'enrolled' => $classroom->students->count()];
    }

    private function toMinutes(string $value): int
    {
        $value = $this->normalizeDigits(trim($value));

        if (preg_match('/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/', $value, $parts)) {
            $minutes = ((int) $parts[1] * 60) + (int) $parts[2];
            if (isset($parts[3]) && (int) $parts[3] >= 30) {
                $minutes++;
            }

            return $minutes;
        }

        $value = str_replace([',', '٬'], '', $value);

        return ctype_digit($value) ? (int) $value : 0;
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}