<?php

namespace App\Services\Learning;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\User;
use App\Notifications\AttendanceAbsenceNotification;

final class AttendanceGuardianNotifier
{
    /**
     * Notify each active guardian once when a student reaches three absences
     * in the same classroom. Database notifications remain available even
     * while the SMS provider is not configured.
     *
     * @param array<int, array{status:string}> $entries
     */
    public function notifyThresholds(Classroom $classroom, array $entries): void
    {
        foreach ($entries as $studentId => $entry) {
            if (($entry['status'] ?? null) !== 'absent') {
                continue;
            }

            $student = $classroom->students()->whereKey((int) $studentId)->first();
            if (! $student) {
                continue;
            }

            $absenceCount = Attendance::query()
                ->where('classroom_id', $classroom->id)
                ->where('student_id', $student->id)
                ->where('status', 'absent')
                ->whereHas('session', function ($sessions): void {
                    $sessions->where('status', '!=', 'cancelled')
                        ->where('starts_at', '<=', now())
                        ->whereColumn('class_sessions.classroom_id', 'attendances.classroom_id');
                })
                ->count();

            if ($absenceCount < 3) {
                continue;
            }

            $dedupeKey = 'attendance-absence:'.$classroom->id.':'.$student->id;
            $guardians = $student->guardians()
                ->where('users.is_active', true)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'parent'))
                ->get();

            foreach ($guardians as $guardian) {
                $alreadyNotified = $guardian->notifications()
                    ->where('type', AttendanceAbsenceNotification::class)
                    ->where('data->dedupe_key', $dedupeKey)
                    ->exists();

                if (! $alreadyNotified) {
                    $guardian->notify(new AttendanceAbsenceNotification(
                        $student,
                        $classroom->name,
                        $absenceCount,
                        (int) $classroom->id,
                    ));
                }
            }
        }
    }
}
