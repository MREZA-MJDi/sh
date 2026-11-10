<?php

namespace App\Services\Admin;

use App\Models\Attendance;
use App\Models\Classroom;
use Illuminate\Support\Facades\DB;

final class AttendanceService
{
    public function record(Classroom $classroom, string $sessionDate, array $entries, int $markedBy): void
    {
        DB::transaction(function () use ($classroom, $sessionDate, $entries, $markedBy): void {
            foreach ($entries as $studentId => $entry) {
                Attendance::updateOrCreate(
                    [
                        'classroom_id' => $classroom->id,
                        'student_id' => (int) $studentId,
                        'session_date' => $sessionDate,
                    ],
                    [
                        'status' => $entry['status'],
                        'note' => $entry['note'] ?? null,
                        'marked_by' => $markedBy,
                    ]
                );
            }
        });
    }
}
