<?php

namespace App\Services\Admin;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Classroom;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AttendanceService
{
    /**
     * @param array<int, array{status:string,note?:string|null,joined_at?:string|null,attended_minutes?:int|string|null}> $entries
     */
    public function record(Classroom $classroom, ClassSession $session, array $entries, int $markedBy): void
    {
        abort_unless((int) $session->classroom_id === (int) $classroom->id, 404);

        $scheduledMinutes = null;
        if ($session->starts_at && $session->ends_at) {
            $seconds = $session->ends_at->getTimestamp() - $session->starts_at->getTimestamp();
            $scheduledMinutes = $seconds > 0 ? intdiv($seconds, 60) : null;
        }

        DB::transaction(function () use ($classroom, $session, $entries, $markedBy, $scheduledMinutes): void {
            foreach ($entries as $studentId => $entry) {
                $joinedAt = filled($entry['joined_at'] ?? null)
                    ? Carbon::parse($entry['joined_at'])
                    : null;
                $attendedMinutes = filled($entry['attended_minutes'] ?? null)
                    ? (int) $entry['attended_minutes']
                    : null;

                if ($scheduledMinutes !== null && $attendedMinutes !== null && $attendedMinutes > $scheduledMinutes) {
                    throw ValidationException::withMessages([
                        "attendance.{$studentId}.attended_minutes" => 'مدت حضور نمی‌تواند از مدت واقعی برنامه‌ریزی‌شده بیشتر باشد.',
                    ]);
                }

                if ($entry['status'] === 'absent') {
                    $joinedAt = null;
                    $attendedMinutes = null;
                }

                Attendance::updateOrCreate(
                    [
                        'class_session_id' => $session->id,
                        'student_id' => (int) $studentId,
                    ],
                    [
                        'classroom_id' => $classroom->id,
                        'session_date' => $session->starts_at->toDateString(),
                        'scheduled_minutes' => $scheduledMinutes,
                        'attended_minutes' => $attendedMinutes,
                        'joined_at' => $joinedAt,
                        'status' => $entry['status'],
                        'note' => filled($entry['note'] ?? null) ? trim($entry['note']) : null,
                        'marked_by' => $markedBy,
                    ]
                );
            }
        });
    }
}