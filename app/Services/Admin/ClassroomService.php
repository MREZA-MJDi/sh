<?php

namespace App\Services\Admin;

use App\Models\Classroom;
use App\Models\ClassroomMembershipHistory;
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

            $this->syncRoster($classroom, $data['student_ids'] ?? [], $actor);
            $this->enrollments->syncClassroomRoster($classroom);

            return $classroom->refresh();
        }, 3);
    }

    public function update(User $actor, Classroom $classroom, array $data): Classroom
    {
        $classroom = $this->workspace->queryFor($actor)->findOrFail($classroom->id);
        $teacherId = $this->workspace->isTeacher($actor) ? $actor->id : (int) $data['teacher_id'];
        $previousCourseId = $classroom->course_id;

        return DB::transaction(function () use ($classroom, $data, $actor, $teacherId, $previousCourseId): Classroom {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);

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

            $this->syncRoster($classroom, $data['student_ids'] ?? [], $actor);

            if ($previousCourseId && $previousCourseId !== $classroom->course_id) {
                $previousCourse = Course::query()->find($previousCourseId);
                if ($previousCourse) {
                    $this->enrollments->syncCourseRoster($previousCourse);
                }
            }

            $this->enrollments->syncClassroomRoster($classroom->fresh());

            return $classroom->refresh();
        }, 3);
    }

    /**
     * Keep the live pivot limited to current members. Every removal is written
     * to an append-only history table, so a student may later rejoin the class.
     */
    private function syncRoster(Classroom $classroom, array $requestedStudentIds, User $actor): void
    {
        $requestedIds = collect($requestedStudentIds)->map(static fn ($id): int => (int) $id)->unique()->values();
        $current = DB::table('classroom_student')
            ->where('classroom_id', $classroom->id)
            ->lockForUpdate()
            ->get(['student_id', 'assigned_by', 'created_at']);

        $currentIds = $current->pluck('student_id')->map(static fn ($id): int => (int) $id);
        $removedIds = $currentIds->diff($requestedIds);
        $addedIds = $requestedIds->diff($currentIds);

        if ($removedIds->isNotEmpty()) {
            $now = now();
            $historyRows = $current->whereIn('student_id', $removedIds->all())
                ->map(static fn ($membership): array => [
                    'classroom_id' => $classroom->id,
                    'student_id' => $membership->student_id,
                    'assigned_by' => $membership->assigned_by,
                    'removed_by' => $actor->id,
                    'joined_at' => $membership->created_at ?? $now,
                    'left_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

            ClassroomMembershipHistory::query()->insert($historyRows);
            DB::table('classroom_student')
                ->where('classroom_id', $classroom->id)
                ->whereIn('student_id', $removedIds->all())
                ->delete();
        }

        if ($addedIds->isNotEmpty()) {
            $classroom->students()->attach(
                $addedIds->all(),
                ['assigned_by' => $actor->id]
            );
        }
    }

    public function delete(User $actor, Classroom $classroom): void
    {
        $classroom = $this->workspace->queryFor($actor)->findOrFail($classroom->id);

        if (
            $classroom->homeworks()->exists()
            || $classroom->attendances()->exists()
            || DB::table('classroom_student')->where('classroom_id', $classroom->id)->exists()
            || $classroom->membershipHistory()->exists()
            || $classroom->exams()->exists()
            || $classroom->learningResources()->exists()
            || $classroom->spotPlayerVideos()->exists()
        ) {
            throw ValidationException::withMessages([
                'classroom' => 'کلاس دارای سوابق آموزشی یا اعضای ثبت‌نام‌شده است؛ آن را بایگانی کنید.',
            ]);
        }

        DB::transaction(fn () => $classroom->delete(), 3);
    }
}
