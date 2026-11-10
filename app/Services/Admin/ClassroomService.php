<?php

namespace App\Services\Admin;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ClassroomService
{
    public function __construct(
        private readonly ClassroomWorkspaceService $workspace,
        private readonly CourseEnrollmentService $enrollments,
    ) {}

    public function create(User $actor, array $data): Classroom
    {
        $teacherId = $this->workspace->isTeacher($actor) ? $actor->id : (int) $data['teacher_id'];

        return DB::transaction(function () use ($data, $actor, $teacherId): Classroom {
            $classroom = Classroom::create([
                'name' => $data['name'],
                'course_id' => $data['course_id'] ?? null,
                'teacher_id' => $teacherId,
                'meeting_url' => $data['meeting_url'] ?? null,
                'status' => $data['status'],
                'schedule' => [
                    'days' => $data['schedule_days'] ?? [],
                    'time' => $data['schedule_time'] ?? null,
                    'duration_minutes' => (int) ($data['duration_minutes'] ?? 90),
                ],
                'created_by' => $actor->id,
            ]);

            $classroom->students()->syncWithPivotValues(
                $data['student_ids'] ?? [],
                ['assigned_by' => $actor->id]
            );

            $this->enrollments->syncClassroomRoster($classroom);

            return $classroom->refresh();
        });
    }

    public function update(User $actor, Classroom $classroom, array $data): Classroom
    {
        $classroom = $this->workspace->queryFor($actor)->findOrFail($classroom->id);
        $teacherId = $this->workspace->isTeacher($actor) ? $actor->id : (int) $data['teacher_id'];
        $previousCourseId = $classroom->course_id;

        return DB::transaction(function () use ($classroom, $data, $actor, $teacherId, $previousCourseId): Classroom {
            $classroom->update([
                'name' => $data['name'],
                'course_id' => $data['course_id'] ?? null,
                'teacher_id' => $teacherId,
                'meeting_url' => $data['meeting_url'] ?? null,
                'status' => $data['status'],
                'schedule' => [
                    'days' => $data['schedule_days'] ?? [],
                    'time' => $data['schedule_time'] ?? null,
                    'duration_minutes' => (int) ($data['duration_minutes'] ?? 90),
                ],
            ]);

            $classroom->students()->syncWithPivotValues(
                $data['student_ids'] ?? [],
                ['assigned_by' => $actor->id]
            );

            if ($previousCourseId && $previousCourseId !== $classroom->course_id) {
                $previousCourse = Course::find($previousCourseId);
                if ($previousCourse) {
                    $this->enrollments->syncCourseRoster($previousCourse);
                }
            }

            $this->enrollments->syncClassroomRoster($classroom->fresh());

            return $classroom->refresh();
        });
    }

    public function delete(User $actor, Classroom $classroom): void
    {
        $classroom = $this->workspace->queryFor($actor)->findOrFail($classroom->id);

        if (
            $classroom->homeworks()->exists()
            || $classroom->attendances()->exists()
            || $classroom->students()->exists()
        ) {
            throw ValidationException::withMessages([
                'classroom' => 'کلاس دارای سوابق آموزشی یا اعضای ثبت‌نام‌شده است؛ آن را بایگانی کنید.',
            ]);
        }

        DB::transaction(fn () => $classroom->delete());
    }
}
