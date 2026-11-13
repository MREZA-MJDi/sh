<?php

namespace App\Services\Admin;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Classroom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AttendanceService
{
    public function record(Classroom $classroom, string $sessionDate, array $entries, int $markedBy): void
    {
        DB::transaction(function () use ($classroom, $sessionDate, $entries, $markedBy): void {
            Classroom::query()->whereKey($classroom->id)->lockForUpdate()->firstOrFail();

            $session = ClassSession::query()
                ->where('classroom_id', $classroom->id)
                ->whereDate('starts_at', $sessionDate)
                ->orderBy('starts_at')
                ->first();

            if (! $session) {
                $schedule = $classroom->schedule ?? [];
                $startTime = $schedule['time'] ?? '00:00';
                $startsAt = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $sessionDate.' '.$startTime,
                    config('app.timezone')
                );
                $duration = (int) ($schedule['duration_minutes'] ?? 0);

                $session = ClassSession::query()->create([
                    'classroom_id' => $classroom->id,
                    'title' => 'جلسه حضور و غیاب '.$sessionDate,
                    'session_type' => filled($schedule['time'] ?? null) ? 'manual_attendance' : 'manual_unscheduled',
                    'starts_at' => $startsAt,
                    'ends_at' => $duration > 0 ? $startsAt->copy()->addMinutes($duration) : null,
                    'status' => 'completed',
                    'created_by' => $markedBy,
                ]);
            }

            foreach ($entries as $studentId => $entry) {
                $joinedAt = $this->timeOnDate($sessionDate, $entry['joined_at'] ?? null);
                $leftAt = $this->timeOnDate($sessionDate, $entry['left_at'] ?? null);

                if ($joinedAt && $leftAt && ! $leftAt->greaterThan($joinedAt)) {
                    throw ValidationException::withMessages([
                        "attendance.{$studentId}.left_at" => 'زمان خروج باید بعد از زمان ورود باشد.',
                    ]);
                }

                Attendance::updateOrCreate(
                    [
                        'classroom_id' => $classroom->id,
                        'student_id' => (int) $studentId,
                        'session_date' => $sessionDate,
                    ],
                    [
                        'class_session_id' => $session->id,
                        'status' => $entry['status'],
                        'joined_at' => $joinedAt,
                        'left_at' => $leftAt,
                        'note' => $entry['note'] ?? null,
                        'marked_by' => $markedBy,
                    ]
                );
            }
        });
    }

    private function timeOnDate(string $date, ?string $time): ?Carbon
    {
        if (blank($time)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time, config('app.timezone'));
    }
}
